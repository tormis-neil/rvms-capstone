<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin \App\Models\InspectionChecklistItem
 */
class InspectionChecklistItemResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'is_bfp_only' => $this->is_bfp_only,
            'sort_order' => $this->sort_order,
        ];
    }
}
