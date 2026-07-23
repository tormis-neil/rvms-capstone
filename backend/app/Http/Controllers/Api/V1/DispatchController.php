<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\CloseDispatchRequest;
use App\Http\Requests\StoreDispatchRequest;
use App\Http\Resources\DispatchResource;
use App\Http\Resources\VehicleResource;
use App\Models\Dispatch;
use App\Models\Vehicle;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Dispatch logs API (FR-15, FR-16, FR-17) — admin only. Agency scoping is
 * automatic (BelongsToAgency), so a cross-agency {dispatch} binding 404s.
 */
class DispatchController extends Controller
{
    public function index(Request $request)
    {
        $dispatches = Dispatch::query()
            ->with(['vehicle', 'driver'])
            ->when($request->boolean('active'), fn ($q) => $q->active())
            ->when($request->filled('vehicle_id'), fn ($q) => $q->where('vehicle_id', $request->integer('vehicle_id')))
            ->latest('time_out')
            ->latest('id')
            ->paginate(10);

        return DispatchResource::collection($dispatches);
    }

    /**
     * POST /dispatches — open a dispatch (FR-15). The vehicle is set to
     * Dispatched. mission_other is cleared unless the mission is "Others".
     */
    public function store(StoreDispatchRequest $request)
    {
        $dispatch = DB::transaction(function () use ($request) {
            $data = $request->validated();
            if ($data['mission_type'] !== Dispatch::MISSION_OTHERS) {
                $data['mission_other'] = null;
            }

            // agency_id is auto-stamped from the authenticated admin (BelongsToAgency).
            $dispatch = Dispatch::create($data);

            $dispatch->vehicle()->update(['status' => Vehicle::STATUS_DISPATCHED]);

            return $dispatch;
        });

        return DispatchResource::make($dispatch->load(['vehicle', 'driver']))
            ->response()
            ->setStatusCode(201);
    }

    public function show(Dispatch $dispatch)
    {
        return DispatchResource::make($dispatch->load(['vehicle', 'driver']));
    }

    /**
     * PUT /dispatches/{id} — edit an open dispatch's details (FR-15).
     */
    public function update(StoreDispatchRequest $request, Dispatch $dispatch)
    {
        $data = $request->validated();
        if ($data['mission_type'] !== Dispatch::MISSION_OTHERS) {
            $data['mission_other'] = null;
        }

        $dispatch->update($data);

        return DispatchResource::make($dispatch->fresh()->load(['vehicle', 'driver']));
    }

    /**
     * PATCH /dispatches/{id}/close — close a dispatch (FR-16). Records the time
     * in and return status (written to the vehicle). When the time-in odometer
     * is present and higher than the vehicle's current mileage, it updates the
     * mileage (mileage-on-arrival → feeds mileage-based PM, FR-14).
     */
    public function close(CloseDispatchRequest $request, Dispatch $dispatch)
    {
        DB::transaction(function () use ($request, $dispatch) {
            $odometerIn = $request->validated('odometer_in');

            $dispatch->update([
                'time_in' => $request->validated('time_in'),
                'odometer_in' => $odometerIn,
                'return_status' => $request->validated('return_status'),
                'remarks' => $request->validated('remarks'),
            ]);

            $vehicle = $dispatch->vehicle;
            $attributes = ['status' => $request->validated('return_status')];

            if ($odometerIn !== null && $odometerIn > (int) $vehicle->current_mileage) {
                $attributes['current_mileage'] = $odometerIn;
            }

            $vehicle->update($attributes);
        });

        return DispatchResource::make($dispatch->fresh()->load(['vehicle', 'driver']));
    }

    /**
     * GET /vehicles/availability — every agency vehicle with its live status (FR-17).
     */
    public function availability(Request $request)
    {
        $vehicles = Vehicle::query()
            ->with('assignedDriver')
            ->orderBy('plate_number')
            ->get();

        return VehicleResource::collection($vehicles);
    }
}
