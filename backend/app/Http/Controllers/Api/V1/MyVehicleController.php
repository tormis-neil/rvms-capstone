<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\VehicleResource;
use App\Models\Vehicle;
use Illuminate\Http\Request;

/**
 * Driver's assigned vehicle(s) (FR-07, FR-09). A driver may crew more than one
 * vehicle, as its primary OR its secondary driver — all of them are returned
 * (2026-09: secondary driver added).
 */
class MyVehicleController extends Controller
{
    public function __invoke(Request $request)
    {
        $driverId = $request->user()->id;

        $vehicles = Vehicle::query()
            ->with(['assignedDriver', 'secondaryDriver'])
            ->where(fn ($q) => $q
                ->where('assigned_driver_id', $driverId)
                ->orWhere('secondary_driver_id', $driverId))
            ->orderBy('plate_number')
            ->get();

        return VehicleResource::collection($vehicles);
    }
}
