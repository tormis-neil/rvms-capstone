<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\InspectionRequest;
use App\Http\Resources\InspectionResource;
use App\Models\Inspection;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

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
}
