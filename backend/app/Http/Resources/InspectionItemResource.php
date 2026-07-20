<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin \App\Models\InspectionItem
 */
class InspectionItemResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'checklist_item_id' => $this->checklist_item_id,
            'name' => $this->whenLoaded('checklistItem', fn () => $this->checklistItem->name),
            'is_bfp_only' => $this->whenLoaded('checklistItem', fn () => $this->checklistItem->is_bfp_only),
            'status' => $this->status,
            'remarks' => $this->remarks,
        ];
    }
}
