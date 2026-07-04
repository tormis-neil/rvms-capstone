<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\VehicleResource;
use App\Models\Vehicle;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * GET /api/v1/my-vehicle — the authenticated driver's assigned vehicle
 * and its current status (FR-07). Driver token only.
 */
class MyVehicleController extends Controller
{
    public function show(Request $request): JsonResponse
    {
        $vehicle = Vehicle::with('assignedDriver')
            ->where('assigned_driver_id', $request->user()->id)
            ->first();

        if ($vehicle === null) {
            return response()->json([
                'message' => 'No vehicle is currently assigned to you.',
                'data' => null,
            ]);
        }

        return (new VehicleResource($vehicle))->response();
    }
}
