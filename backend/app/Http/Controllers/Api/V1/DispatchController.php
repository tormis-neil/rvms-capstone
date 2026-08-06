<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\CloseDispatchRequest;
use App\Http\Requests\StoreDispatchRequest;
use App\Http\Resources\DispatchResource;
use App\Http\Resources\VehicleResource;
use App\Models\Dispatch;
use App\Models\Vehicle;
use App\Services\DispatchGuard;
use App\Services\DispatchReassignment;
use App\Services\VehicleStatusWriter;
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

            // Re-check with the rows locked: two admins submitting in the same
            // instant can both clear validation before either has inserted.
            app(DispatchGuard::class)->assertFree((int) $data['vehicle_id'], (int) $data['driver_id']);

            // agency_id is auto-stamped from the authenticated admin (BelongsToAgency).
            $dispatch = Dispatch::create($data);

            app(VehicleStatusWriter::class)->writeFromDispatch(
                $dispatch->vehicle,
                Vehicle::STATUS_DISPATCHED,
                VehicleStatusWriter::SOURCE_DISPATCH_OPEN,
            );

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
     *
     * Moving an OPEN dispatch onto a different vehicle moves the Dispatched
     * status with it (FR-18) — see DispatchReassignment for why that is not
     * optional.
     */
    public function update(StoreDispatchRequest $request, Dispatch $dispatch)
    {
        DB::transaction(function () use ($request, $dispatch) {
            $data = $request->validated();
            if ($data['mission_type'] !== Dispatch::MISSION_OTHERS) {
                $data['mission_other'] = null;
            }

            $previousVehicleId = (int) $dispatch->vehicle_id;

            // Same locked re-check as store(), ignoring this dispatch so
            // re-saving it is never a clash with its own record.
            if ($dispatch->isActive()) {
                app(DispatchGuard::class)->assertFree(
                    (int) $data['vehicle_id'],
                    (int) $data['driver_id'],
                    $dispatch->id,
                );
            }

            $dispatch->update($data);

            app(DispatchReassignment::class)->handOver($dispatch, $previousVehicleId);
        });

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
            $extra = [];

            if ($odometerIn !== null && $odometerIn > (int) $vehicle->current_mileage) {
                $extra['current_mileage'] = $odometerIn;
            }

            // The dispatch is now closed, so this write is the one that releases
            // the vehicle from its Dispatched state (FR-16).
            app(VehicleStatusWriter::class)->writeFromDispatch(
                $vehicle,
                $request->validated('return_status'),
                VehicleStatusWriter::SOURCE_DISPATCH_CLOSE,
                $extra,
            );
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
