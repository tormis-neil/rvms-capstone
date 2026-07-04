<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin \App\Models\Inspection
 */
class InspectionResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'agency_id' => $this->agency_id,
            'vehicle_id' => $this->vehicle_id,
            'driver_id' => $this->driver_id,
            'inspection_date' => $this->inspection_date->toDateString(),
            'review_status' => $this->review_status,
            'reviewed_by' => $this->reviewed_by,
            'reviewed_at' => $this->reviewed_at,
            'result_label' => $this->whenLoaded('items', fn () => $this->resultLabel()),
            'issue_count' => $this->whenLoaded('items', fn () => $this->issueCount()),
            'vehicle' => new VehicleResource($this->whenLoaded('vehicle')),
            'driver' => new UserResource($this->whenLoaded('driver')),
            'reviewer' => new UserResource($this->whenLoaded('reviewer')),
            'items' => $this->whenLoaded('items', fn () => $this->items->map(fn ($item) => [
                'id' => $item->id,
                'checklist_item_id' => $item->checklist_item_id,
                'name' => $item->checklistItem->name,
                'status' => $item->status,
                'remarks' => $item->remarks,
            ])),
            'created_at' => $this->created_at,
        ];
    }
}
