<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\VehicleResource;
use App\Models\Vehicle;
use Illuminate\Http\Request;

/**
 * Driver's assigned vehicle(s) (FR-07). A driver may be the primary driver
 * of more than one vehicle — all of them are returned.
 */
class MyVehicleController extends Controller
{
    public function __invoke(Request $request)
    {
        $vehicles = Vehicle::query()
            ->where('assigned_driver_id', $request->user()->id)
            ->orderBy('plate_number')
            ->get();

        return VehicleResource::collection($vehicles);
    }
}
