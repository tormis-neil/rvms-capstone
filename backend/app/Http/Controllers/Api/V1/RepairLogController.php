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
        $repair = RepairLog::create($this->payload($request));

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
        $repair->update($this->payload($request));

        return RepairLogResource::make($repair->fresh()->load(['vehicle', 'driver']));
    }

    /**
     * Same shape as the Blade twin's payload(): store the upload, and drop both
     * external-shop fields when the source is in-house (FR-13, 2026-08).
     */
    private function payload(StoreRepairLogRequest $request): array
    {
        $data = $request->validated();

        // `receipt` is the upload; `receipt_path` is what the record stores.
        unset($data['receipt']);

        if ($path = $request->storeReceipt()) {
            $data['receipt_path'] = $path;
        }

        if ($data['repair_source'] !== RepairLog::SOURCE_EXTERNAL) {
            $data['external_shop_name'] = null;
            $data['receipt_path'] = null;
        }

        return $data;
    }
}
