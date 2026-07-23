<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Http\Requests\CloseDispatchRequest;
use App\Http\Requests\StoreDispatchRequest;
use App\Models\Dispatch;
use App\Models\User;
use App\Models\Vehicle;
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
        $dispatches = Dispatch::query()
            ->with(['vehicle', 'driver'])
            ->latest('time_out')
            ->latest('id')
            ->get();

        $activeCount = $dispatches->whereNull('time_in')->count();

        // New-dispatch selects: Operational vehicles (only these may be dispatched)
        // and the agency's drivers.
        $vehicles = Vehicle::query()
            ->where('status', Vehicle::STATUS_OPERATIONAL)
            ->orderBy('plate_number')
            ->get(['id', 'plate_number', 'type', 'status']);

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

            $dispatch = Dispatch::create($data);
            $dispatch->vehicle()->update(['status' => Vehicle::STATUS_DISPATCHED]);
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
            $attributes = ['status' => $request->validated('return_status')];

            // Mileage-on-arrival (FR-16 → FR-14): only ever increases mileage.
            if ($odometerIn !== null && $odometerIn > (int) $vehicle->current_mileage) {
                $attributes['current_mileage'] = $odometerIn;
            }

            $vehicle->update($attributes);
        });

        return redirect()->route('dispatch')->with('status', 'Dispatch closed.');
    }
}
