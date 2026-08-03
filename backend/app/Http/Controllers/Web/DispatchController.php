<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Http\Requests\CloseDispatchRequest;
use App\Http\Requests\StoreDispatchRequest;
use App\Models\Dispatch;
use App\Models\User;
use App\Models\Vehicle;
use App\Services\DispatchGuard;
use App\Services\VehicleStatusWriter;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

/**
 * Dispatch Logging dashboard page (FR-15, FR-16, FR-17) — the Blade twin of the
 * /api/v1/dispatches endpoints. Agency-scoped.
 */
class DispatchController extends Controller
{
    public function index(Request $request): View
    {
        // Paginated (R10.4, NFR-01): a logbook only ever grows. The active
        // banner counts the TRUE total with its own query, so it can never be
        // trimmed to whatever happens to be on the current page.
        $dispatches = Dispatch::query()
            ->with(['vehicle', 'driver'])
            ->latest('time_out')
            ->latest('id')
            ->paginate(10)
            ->withQueryString();

        $activeCount = Dispatch::query()->whereNull('time_in')->count();

        // New-dispatch selects: Operational vehicles (only these may be dispatched)
        // and the agency's drivers. assigned_driver_id rides along so the modal can
        // preselect each vehicle's primary driver (FR-05 → FR-15).
        $vehicles = Vehicle::query()
            ->where('status', Vehicle::STATUS_OPERATIONAL)
            ->orderBy('plate_number')
            ->get(['id', 'plate_number', 'type', 'status', 'assigned_driver_id']);

        $drivers = User::query()
            ->drivers()
            ->where('agency_id', $request->user()->agency_id)
            ->where('status', User::STATUS_ACTIVE)
            ->orderBy('name')
            ->get(['id', 'name']);

        return view('dispatch', compact('dispatches', 'activeCount', 'vehicles', 'drivers'));
    }

    public function store(StoreDispatchRequest $request): RedirectResponse
    {
        DB::transaction(function () use ($request) {
            $data = $request->validated();
            if ($data['mission_type'] !== Dispatch::MISSION_OTHERS) {
                $data['mission_other'] = null;
            }

            // Re-check with the rows locked: two admins submitting in the same
            // instant can both clear validation before either has inserted.
            app(DispatchGuard::class)->assertFree((int) $data['vehicle_id'], (int) $data['driver_id']);

            $dispatch = Dispatch::create($data);

            app(VehicleStatusWriter::class)->writeFromDispatch(
                $dispatch->vehicle,
                Vehicle::STATUS_DISPATCHED,
                VehicleStatusWriter::SOURCE_DISPATCH_OPEN,
            );
        });

        return redirect()->route('dispatch')->with('status', 'Vehicle dispatched.');
    }

    public function update(StoreDispatchRequest $request, Dispatch $dispatch): RedirectResponse
    {
        $data = $request->validated();
        if ($data['mission_type'] !== Dispatch::MISSION_OTHERS) {
            $data['mission_other'] = null;
        }

        $dispatch->update($data);

        return redirect()->route('dispatch')->with('status', 'Dispatch updated.');
    }

    public function close(CloseDispatchRequest $request, Dispatch $dispatch): RedirectResponse
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

            // Mileage-on-arrival (FR-16 → FR-14): only ever increases mileage.
            if ($odometerIn !== null && $odometerIn > (int) $vehicle->current_mileage) {
                $extra['current_mileage'] = $odometerIn;
            }

            // The dispatch is now closed, so this write releases the vehicle
            // from its Dispatched state (FR-16).
            app(VehicleStatusWriter::class)->writeFromDispatch(
                $vehicle,
                $request->validated('return_status'),
                VehicleStatusWriter::SOURCE_DISPATCH_CLOSE,
                $extra,
            );
        });

        return redirect()->route('dispatch')->with('status', 'Dispatch closed.');
    }
}
