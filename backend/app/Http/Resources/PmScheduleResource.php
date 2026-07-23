<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin \App\Models\PmSchedule
 */
class PmScheduleResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'agency_id' => $this->agency_id,
            'vehicle_id' => $this->vehicle_id,
            'service_target' => $this->service_target,
            'pm_type' => $this->pm_type,
            'interval_km' => $this->interval_km,
            'last_pm_mileage' => $this->last_pm_mileage,
            'due_mileage' => $this->due_mileage,
            'due_date' => $this->due_date?->toDateString(),
            'due_soon_threshold_km' => $this->due_soon_threshold_km,
            'due_soon_threshold_days' => $this->due_soon_threshold_days,
            'status' => $this->status,
            'date_serviced' => $this->date_serviced?->toDateString(),
            'completion_repair_source' => $this->completion_repair_source,
            'completion_parts_replaced' => $this->completion_parts_replaced,
            'completion_remarks' => $this->completion_remarks,
            'vehicle' => $this->whenLoaded('vehicle', fn () => [
                'id' => $this->vehicle->id,
                'plate_number' => $this->vehicle->plate_number,
                'type' => $this->vehicle->type,
                'current_mileage' => $this->vehicle->current_mileage,
            ]),
        ];
    }
}
