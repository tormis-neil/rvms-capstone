<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\InspectionChecklistItemResource;
use App\Models\InspectionChecklistItem;
use Illuminate\Http\Request;

/**
 * GET /api/v1/inspections/checklist (FR-09) — the checklist a driver fills.
 * BFP drivers get all 14 items (12 standard + Hydraulic System + Fire Pump);
 * every other agency's drivers get the 12 standard items.
 */
class InspectionChecklistController extends Controller
{
    public function __invoke(Request $request)
    {
        $agencyCode = $request->user()->agency->code;

        $items = InspectionChecklistItem::query()
            ->forAgencyCode($agencyCode)
            ->get();

        return InspectionChecklistItemResource::collection($items);
    }
}
