<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\ReviewDamageReportRequest;
use App\Http\Requests\StoreDamageReportRequest;
use App\Http\Resources\DamageReportResource;
use App\Models\DamageReport;
use App\Models\User;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Damage reports API (FR-11 submission, FR-12 review). Agency scoping on
 * DamageReport is automatic (BelongsToAgency), so a cross-agency {damageReport}
 * binding resolves to 404.
 */
class DamageReportController extends Controller
{
    /**
     * POST /damage-reports (driver) — file a damage report (FR-11). Photo is
     * optional; the date reported is auto-set; the report starts Pending.
     */
    public function store(StoreDamageReportRequest $request)
    {
        $photoPath = $request->hasFile('photo')
            ? $request->file('photo')->store('damage-photos', 'public')
            : null;

        // agency_id is auto-stamped from the authenticated driver (BelongsToAgency).
        $report = DamageReport::create([
            'vehicle_id' => $request->validated('vehicle_id'),
            'driver_id' => $request->user()->id,
            'nature_of_damage' => $request->validated('nature_of_damage'),
            'suspected_parts' => $request->validated('suspected_parts'),
            'photo_path' => $photoPath,
            'date_reported' => now()->toDateString(),
            'status' => DamageReport::STATUS_PENDING,
        ]);

        return DamageReportResource::make($report->load(['vehicle', 'driver']))
            ->response()
            ->setStatusCode(201);
    }

    /**
     * GET /damage-reports — history list. A driver sees only their OWN reports
     * (FR-11); an admin sees the whole agency's, newest first (FR-12).
     */
    public function index(Request $request)
    {
        $user = $request->user();

        $query = DamageReport::query()->with(['vehicle', 'driver']);

        if ($user->role === User::ROLE_DRIVER) {
            $query->where('driver_id', $user->id);
        } else {
            $query->when($request->filled('status'), fn ($q) => $q->where('status', $request->string('status')));
        }

        $reports = $query->latest('date_reported')->latest('id')->paginate(10);

        return DamageReportResource::collection($reports);
    }

    /**
     * GET /damage-reports/{id}. A driver may only open their own report.
     */
    public function show(Request $request, DamageReport $damageReport)
    {
        $user = $request->user();

        if ($user->role === User::ROLE_DRIVER && $damageReport->driver_id !== $user->id) {
            throw new NotFoundHttpException;
        }

        return DamageReportResource::make($damageReport->load(['vehicle', 'driver', 'reviewer']));
    }

    /**
     * PATCH /damage-reports/{id}/review (admin) — mark Reviewed, record
     * who/when, and optionally change the vehicle's status (FR-12 + FR-18).
     */
    public function review(ReviewDamageReportRequest $request, DamageReport $damageReport)
    {
        $damageReport->update([
            'status' => DamageReport::STATUS_REVIEWED,
            'reviewed_by' => $request->user()->id,
            'reviewed_at' => now(),
        ]);

        if ($status = $request->validated('vehicle_status')) {
            $damageReport->vehicle()->update(['status' => $status]);
        }

        return DamageReportResource::make(
            $damageReport->fresh()->load(['vehicle', 'driver', 'reviewer'])
        );
    }
}
