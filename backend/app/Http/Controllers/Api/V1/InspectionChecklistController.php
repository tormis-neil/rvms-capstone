<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\InspectionChecklistItem;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * GET /api/v1/inspections/checklist — the BLOWBAGETS checklist for the
 * caller's agency (FR-09): 14 items for BFP, 12 for everyone else.
 */
class InspectionChecklistController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $agency = $request->user()->agency;

        $items = InspectionChecklistItem::forAgency($agency)->get();

        return response()->json([
            'agency_code' => $agency->code,
            'count' => $items->count(),
            'items' => $items->map(fn ($item) => [
                'id' => $item->id,
                'name' => $item->name,
                'is_bfp_only' => $item->is_bfp_only,
                'sort_order' => $item->sort_order,
            ]),
        ]);
    }
}
