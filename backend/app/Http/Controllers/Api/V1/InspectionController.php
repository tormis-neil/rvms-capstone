<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\ReviewInspectionRequest;
use App\Http\Requests\StoreInspectionRequest;
use App\Http\Resources\InspectionResource;
use App\Models\Inspection;
use App\Models\InspectionItem;
use App\Models\Notification;
use App\Models\User;
use App\Services\VehicleStatusWriter;
use App\Services\NotificationDispatcher;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Inspections API (FR-09 submission, FR-10 monitoring/review). Agency scoping
 * on Inspection is automatic (the model uses BelongsToAgency), so a
 * cross-agency {inspection} route binding resolves to 404.
 */
class InspectionController extends Controller
{
    /**
     * POST /inspections (driver) — submit a daily BLOWBAGETS inspection (FR-09).
     */
    public function store(StoreInspectionRequest $request)
    {
        $inspection = DB::transaction(function () use ($request) {
            // agency_id is auto-stamped from the authenticated driver (BelongsToAgency).
            $inspection = Inspection::create([
                'vehicle_id' => $request->validated('vehicle_id'),
                'driver_id' => $request->user()->id,
                'inspection_date' => $request->validated('inspection_date') ?? now()->toDateString(),
                'review_status' => Inspection::STATUS_PENDING,
            ]);

            foreach ($request->validated('items') as $item) {
                $inspection->items()->create([
                    'checklist_item_id' => $item['checklist_item_id'],
                    'status' => $item['status'],
                    'remarks' => $item['status'] === InspectionItem::STATUS_HAS_ISSUE ? $item['remarks'] : null,
                ]);
            }

            return $inspection;
        });

        $this->notifyIfFlagged($inspection);

        return InspectionResource::make($inspection->load(['items.checklistItem', 'vehicle', 'driver']))
            ->response()
            ->setStatusCode(201);
    }


    /**
     * Alerts every administrator of the agency when a submitted inspection
     * reports one or more items as Has Issue (FR-09 → FR-21).
     *
     * An ALL-OK submission notifies nobody, which is the whole point: a driver
     * submits daily per vehicle, so alerting on every submission would bury the
     * genuinely urgent notifications under routine traffic. The Inspections page
     * already carries a live "N Pending Review" pill for the routine volume.
     */
    private function notifyIfFlagged(Inspection $inspection): void
    {
        $flagged = $inspection->items()
            ->where('status', InspectionItem::STATUS_HAS_ISSUE)
            ->with('checklistItem')
            ->get();

        if ($flagged->isEmpty()) {
            return;
        }

        $names = $flagged->map(fn ($item) => $item->checklistItem?->name)->filter()->values();

        $dispatcher = app(NotificationDispatcher::class);
        $dispatcher->sendToMany(
            $dispatcher->adminsOf($inspection->agency_id),
            Notification::TYPE_INSPECTION_FLAGGED,
            'Inspection Reported an Issue',
            sprintf(
                '%s — %d %s flagged: %s.',
                $inspection->vehicle?->plate_number ?? 'A vehicle',
                $names->count(),
                $names->count() === 1 ? 'item' : 'items',
                $names->implode(', '),
            ),
            [
                'plate' => $inspection->vehicle?->plate_number,
                'inspection_id' => $inspection->id,
                'flagged_count' => $names->count(),
                'flagged_items' => $names->all(),
            ],
        );
    }

    /**
     * GET /inspections — inspection history list.
     *
     * A driver sees only their OWN submissions (FR-09 history on the mobile
     * app); an admin sees the whole agency's inspections with optional
     * vehicle/driver/date/status filters (FR-10 monitoring). Agency scoping is
     * automatic via the Inspection model's BelongsToAgency scope.
     */
    public function index(Request $request)
    {
        $user = $request->user();

        $query = Inspection::query()
            ->with(['vehicle', 'driver', 'items.checklistItem']);

        if ($user->role === User::ROLE_DRIVER) {
            $query->where('driver_id', $user->id);
        } else {
            $query
                ->when($request->filled('vehicle_id'), fn ($q) => $q->where('vehicle_id', $request->integer('vehicle_id')))
                ->when($request->filled('driver_id'), fn ($q) => $q->where('driver_id', $request->integer('driver_id')))
                ->when($request->filled('date'), fn ($q) => $q->whereDate('inspection_date', $request->date('date')))
                ->when($request->filled('review_status'), fn ($q) => $q->where('review_status', $request->string('review_status')));
        }

        $inspections = $query
            ->latest('inspection_date')
            ->latest('id')
            ->paginate(10);

        return InspectionResource::collection($inspections);
    }

    /**
     * GET /inspections/{id} — full checklist detail. A driver may only open
     * their own inspection (404 otherwise); an admin may open any of their
     * agency's (cross-agency already 404s via the model scope).
     */
    public function show(Request $request, Inspection $inspection)
    {
        $user = $request->user();

        if ($user->role === User::ROLE_DRIVER && $inspection->driver_id !== $user->id) {
            throw new NotFoundHttpException;
        }

        return InspectionResource::make(
            $inspection->load(['items.checklistItem', 'vehicle', 'driver', 'reviewer'])
        );
    }

    /**
     * GET /inspections/frequent-issues (admin) — Has-Issue counts grouped by
     * checklist item with the last-reported date, ranked most frequent first (FR-10).
     */
    public function frequentIssues(Request $request)
    {
        $rows = InspectionItem::query()
            ->join('inspections', 'inspections.id', '=', 'inspection_items.inspection_id')
            ->join('inspection_checklist_items', 'inspection_checklist_items.id', '=', 'inspection_items.checklist_item_id')
            ->where('inspections.agency_id', $request->user()->agency_id)
            ->where('inspection_items.status', InspectionItem::STATUS_HAS_ISSUE)
            ->groupBy('inspection_items.checklist_item_id', 'inspection_checklist_items.name')
            ->select([
                'inspection_items.checklist_item_id',
                'inspection_checklist_items.name',
                DB::raw('COUNT(*) as count'),
                DB::raw('MAX(inspections.inspection_date) as last_reported'),
            ])
            ->orderByDesc('count')
            ->orderBy('inspection_checklist_items.name')
            ->get()
            ->map(fn ($row) => [
                'checklist_item_id' => (int) $row->checklist_item_id,
                'issue' => $row->name,
                'count' => (int) $row->count,
                'last_reported' => $row->last_reported
                    ? Carbon::parse($row->last_reported)->toDateString()
                    : null,
            ]);

        return response()->json(['data' => $rows]);
    }

    /**
     * PATCH /inspections/{id}/review (admin) — mark Reviewed, record who/when,
     * and optionally change the vehicle's operational status (FR-10 + FR-18).
     */
    public function review(ReviewInspectionRequest $request, Inspection $inspection)
    {
        $inspection->update([
            'review_status' => Inspection::STATUS_REVIEWED,
            'reviewed_by' => $request->user()->id,
            'reviewed_at' => now(),
        ]);

        if ($status = $request->validated('vehicle_status')) {
            app(VehicleStatusWriter::class)->write(
                $inspection->vehicle,
                $status,
                VehicleStatusWriter::SOURCE_INSPECTION,
            );
        }

        return InspectionResource::make(
            $inspection->fresh()->load(['items.checklistItem', 'vehicle', 'driver', 'reviewer'])
        );
    }
}
