<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\InspectionRequest;
use App\Http\Resources\InspectionResource;
use App\Models\Inspection;
use App\Models\Vehicle;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

/**
 * Digital BLOWBAGETS inspections (FR-09/FR-10). Agency-scoped via the
 * Inspection global scope; drivers submit, admins monitor and review.
 */
class InspectionController extends Controller
{
    /**
     * POST /api/v1/inspections — driver submits the daily inspection (FR-09).
     */
    public function store(InspectionRequest $request): JsonResponse
    {
        $inspection = DB::transaction(function () use ($request) {
            $inspection = Inspection::create([
                'vehicle_id' => $request->input('vehicle_id'),
                'driver_id' => $request->user()->id,
                'inspection_date' => $request->input('inspection_date'),
            ]);

            $inspection->items()->createMany($request->input('items'));

            return $inspection;
        });

        return (new InspectionResource($inspection->load('items.checklistItem', 'vehicle')))
            ->response()
            ->setStatusCode(201);
    }

    /**
     * GET /api/v1/inspections — admin monitoring list with filters (FR-10).
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $request->validate([
            'vehicle_id' => ['sometimes', 'integer'],
            'driver_id' => ['sometimes', 'integer'],
            'date' => ['sometimes', 'date'],
            'review_status' => ['sometimes', Rule::in([Inspection::REVIEW_PENDING, Inspection::REVIEW_REVIEWED])],
        ]);

        $inspections = Inspection::with(['vehicle', 'driver', 'items.checklistItem'])
            ->when($request->filled('vehicle_id'), fn ($q) => $q->where('vehicle_id', $request->integer('vehicle_id')))
            ->when($request->filled('driver_id'), fn ($q) => $q->where('driver_id', $request->integer('driver_id')))
            ->when($request->filled('date'), fn ($q) => $q->whereDate('inspection_date', $request->date('date')->toDateString()))
            ->when($request->filled('review_status'), fn ($q) => $q->where('review_status', $request->input('review_status')))
            ->orderByDesc('inspection_date')
            ->orderByDesc('id')
            ->paginate(15);

        return InspectionResource::collection($inspections);
    }

    /**
     * GET /api/v1/inspections/frequent-issues — grouped issue counts (FR-10).
     */
    public function frequentIssues(): JsonResponse
    {
        return response()->json(['data' => Inspection::frequentIssues()]);
    }

    /**
     * GET /api/v1/inspections/{id} — one inspection with its checklist.
     */
    public function show(Inspection $inspection): InspectionResource
    {
        return new InspectionResource(
            $inspection->load(['vehicle', 'driver', 'reviewer', 'items.checklistItem']),
        );
    }

    /**
     * PATCH /api/v1/inspections/{id}/review — mark Reviewed, optionally
     * updating the vehicle's single shared status (FR-10, FR-18).
     */
    public function review(Request $request, Inspection $inspection): InspectionResource
    {
        $request->validate([
            'vehicle_status' => ['sometimes', 'required', Rule::in(Vehicle::STATUSES)],
        ]);

        $inspection->update([
            'review_status' => Inspection::REVIEW_REVIEWED,
            'reviewed_by' => $request->user()->id,
            'reviewed_at' => now(),
        ]);

        if ($request->filled('vehicle_status')) {
            $inspection->vehicle->update(['status' => $request->input('vehicle_status')]);
        }

        return new InspectionResource(
            $inspection->load(['vehicle', 'driver', 'reviewer', 'items.checklistItem']),
        );
    }
}
