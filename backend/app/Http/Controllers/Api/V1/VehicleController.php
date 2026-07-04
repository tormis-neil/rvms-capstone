<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\VehicleRequest;
use App\Http\Requests\VehicleStatusRequest;
use App\Http\Resources\VehicleResource;
use App\Models\Vehicle;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/**
 * Vehicle record management for admins (FR-05, FR-18). Every query is
 * agency-scoped by the global AgencyScope, so route-model binding of a
 * vehicle from another agency resolves to 404 automatically.
 */
class VehicleController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $vehicles = Vehicle::with('assignedDriver')
            ->latest()
            ->paginate(15);

        return VehicleResource::collection($vehicles);
    }

    public function store(VehicleRequest $request): JsonResponse
    {
        $vehicle = Vehicle::create($request->validated());

        return (new VehicleResource($vehicle->load('assignedDriver')))
            ->response()
            ->setStatusCode(201);
    }

    public function show(Vehicle $vehicle): VehicleResource
    {
        return new VehicleResource($vehicle->load('assignedDriver'));
    }

    public function update(VehicleRequest $request, Vehicle $vehicle): VehicleResource
    {
        $vehicle->update($request->validated());

        return new VehicleResource($vehicle->load('assignedDriver'));
    }

    /**
     * PATCH /vehicles/{id}/status — write the single shared status (FR-18).
     */
    public function updateStatus(VehicleStatusRequest $request, Vehicle $vehicle): VehicleResource
    {
        $vehicle->update(['status' => $request->validated('status')]);

        return new VehicleResource($vehicle->load('assignedDriver'));
    }
}
