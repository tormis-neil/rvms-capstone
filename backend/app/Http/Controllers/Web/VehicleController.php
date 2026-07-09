<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreVehicleRequest;
use App\Http\Requests\UpdateVehicleStatusRequest;
use App\Models\User;
use App\Models\Vehicle;
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

        $drivers = User::query()
            ->drivers()
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
            'remarks' => ['nullable', 'string'],
        ]);

        $vehicle->update($validated);

        return redirect()->route('vehicles')
            ->with('status', 'Vehicle status updated.');
    }
}
