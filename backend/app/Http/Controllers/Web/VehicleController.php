<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreVehicleRequest;
use App\Models\User;
use App\Models\Vehicle;
use App\Services\VehicleStatusWriter;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * Vehicles dashboard page (FR-05, FR-18) — the Blade twin of the
 * /api/v1/vehicles endpoints, sharing the same form-request rules.
 */
class VehicleController extends Controller
{
    public function index(Request $request): View
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
            ->paginate(10)
            ->withQueryString();

        // Own agency only (FR-02). `users` carries no global agency scope — unlike
        // vehicles/inspections/etc., which use BelongsToAgency — so every User query
        // must filter by hand. Guarded by AgencySelectScopeTest.
        $drivers = User::query()
            ->drivers()
            ->where('agency_id', $request->user()->agency_id)
            ->where('status', User::STATUS_ACTIVE)
            ->orderBy('name')
            ->get(['id', 'name']);

        // Prototype's 5 demo types plus any type already present in the
        // agency's fleet (the schema keeps type free-text per FR-05).
        $types = collect(['Fire Truck', 'Rescue Van', 'Water Tanker', 'Service Vehicle', 'Ambulance'])
            ->merge(Vehicle::query()->distinct()->pluck('type'))
            ->unique()
            ->values();

        return view('vehicles', compact('vehicles', 'drivers', 'types'));
    }

    public function store(StoreVehicleRequest $request): RedirectResponse
    {
        Vehicle::create($request->validated());

        return redirect()->route('vehicles')
            ->with('status', 'Vehicle registered successfully.');
    }

    public function update(StoreVehicleRequest $request, Vehicle $vehicle): RedirectResponse
    {
        $vehicle->update($request->validated());

        return redirect()->route('vehicles')
            ->with('status', 'Vehicle details updated.');
    }

    public function updateStatus(Request $request, Vehicle $vehicle): RedirectResponse
    {
        // The dashboard modal offers only the three manual choices;
        // "Dispatched" is written by the Dispatch module alone (FR-15/FR-18).
        $validated = $request->validate([
            'status' => ['required', Rule::in(Vehicle::MANUAL_STATUSES)],
            // Required since 2026-08 (adviser consultation). A manual status
            // change is the only status write with no other record of why it
            // happened: a dispatch carries its mission, a review carries the
            // report it came from, a PM carries its schedule. This one carried
            // nothing unless somebody chose to type it.
            'remarks' => ['required', 'string', 'max:1000'],
        ], [
            'remarks.required' => 'Give a reason for the status change.',
        ]);

        // Refused (422) when the vehicle is on an active dispatch — design decision 9.
        app(VehicleStatusWriter::class)->write(
            $vehicle,
            $validated['status'],
            VehicleStatusWriter::SOURCE_VEHICLES,
            // Only touch remarks when the form carried the field; an empty value
            // clears the note (documented behaviour, like current_mileage).
            $request->has('remarks') ? ['remarks' => $validated['remarks'] ?? null] : [],
        );

        // Back to the screen the admin pressed the button on, not always Vehicles.
        // Every screen that shows a status can now write one (design decision 9), so
        // a hardcoded destination dumped the admin on a different page and put the
        // confirmation message where they were not looking. `store`/`update` keep
        // their fixed route: those are only ever used from the Vehicles page.
        // Vehicles is the fallback for a request that carries no originating page.
        return back(fallback: route('vehicles'))
            ->with('status', 'Vehicle status updated.');
    }



}
