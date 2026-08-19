<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreVehicleRequest;
use App\Http\Requests\UpdateVehicleStatusRequest;
use App\Http\Resources\VehicleResource;
use App\Models\Vehicle;
use App\Services\VehicleStatusWriter;
use Illuminate\Http\Request;

/**
 * Admin vehicle records API (FR-05, FR-18). Every query is agency-scoped by
 * the AgencyScope global scope; cross-agency ids resolve to 404.
 */
class VehicleController extends Controller
{
    public function index(Request $request)
    {
        $vehicles = Vehicle::query()
            ->with('assignedDriver')
            ->when($request->filled('search'), function ($query) use ($request) {
                $term = '%'.$request->string('search').'%';
                $query->where(fn ($q) => $q
                    ->where('plate_number', 'like', $term)
                    ->orWhere('make', 'like', $term)
                    ->orWhere('model', 'like', $term));
            })
            ->when($request->filled('type'), fn ($q) => $q->where('type', $request->string('type')))
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->string('status')))
            ->orderBy('plate_number')
            ->paginate(10);

        return VehicleResource::collection($vehicles);
    }

    public function store(StoreVehicleRequest $request)
    {
        $vehicle = Vehicle::create($request->validated())->refresh();

        return VehicleResource::make($vehicle->load('assignedDriver'))
            ->response()
            ->setStatusCode(201);
    }

    public function show(Vehicle $vehicle)
    {
        return VehicleResource::make($vehicle->load('assignedDriver'));
    }

    public function update(StoreVehicleRequest $request, Vehicle $vehicle)
    {
        $vehicle->update($request->validated());

        return VehicleResource::make($vehicle->fresh()->load('assignedDriver'));
    }



    public function updateStatus(UpdateVehicleStatusRequest $request, Vehicle $vehicle)
    {
        // Refused (422) when the vehicle is on an active dispatch — design decision 9.
        app(VehicleStatusWriter::class)->write(
            $vehicle,
            $request->validated('status'),
            VehicleStatusWriter::SOURCE_VEHICLES,
            // Only touch remarks when the request carried the field; an empty
            // value clears the note (documented behaviour, like current_mileage).
            $request->has('remarks') ? ['remarks' => $request->validated('remarks')] : [],
        );

        return VehicleResource::make($vehicle->fresh()->load('assignedDriver'));
    }
}
