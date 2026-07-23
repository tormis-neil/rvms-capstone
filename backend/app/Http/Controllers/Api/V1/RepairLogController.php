<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreRepairLogRequest;
use App\Http\Resources\RepairLogResource;
use App\Models\RepairLog;
use Illuminate\Http\Request;

/**
 * Repair logs API (FR-13) — admin only. Agency scoping is automatic
 * (BelongsToAgency), so a cross-agency {repair} binding resolves to 404.
 * External Repair Shop requires the shop name (enforced in the form request).
 */
class RepairLogController extends Controller
{
    public function index(Request $request)
    {
        $repairs = RepairLog::query()
            ->with(['vehicle', 'driver'])
            ->when($request->filled('vehicle_id'), fn ($q) => $q->where('vehicle_id', $request->integer('vehicle_id')))
            ->latest('repair_date')
            ->latest('id')
            ->paginate(10);

        return RepairLogResource::collection($repairs);
    }

    public function store(StoreRepairLogRequest $request)
    {
        // agency_id is auto-stamped from the authenticated admin (BelongsToAgency).
        $repair = RepairLog::create($request->validated());

        return RepairLogResource::make($repair->load(['vehicle', 'driver']))
            ->response()
            ->setStatusCode(201);
    }

    public function show(RepairLog $repair)
    {
        return RepairLogResource::make($repair->load(['vehicle', 'driver']));
    }

    public function update(StoreRepairLogRequest $request, RepairLog $repair)
    {
        $repair->update($request->validated());

        return RepairLogResource::make($repair->fresh()->load(['vehicle', 'driver']));
    }
}
