<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Http\Requests\VehicleRequest;
use App\Http\Requests\VehicleStatusRequest;
use App\Models\User;
use App\Models\Vehicle;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

/**
 * Admin dashboard Vehicles page (FR-05, FR-18). Session/web guard;
 * agency isolation comes from the Vehicle global scope.
 */
class VehicleController extends Controller
{
    public function index(\Illuminate\Http\Request $request): View
    {
        $request->validate([
            'q' => ['sometimes', 'nullable', 'string', 'max:100'],
            'type' => ['sometimes', 'nullable', 'string', 'max:100'],
            'status' => ['sometimes', 'nullable', \Illuminate\Validation\Rule::in(Vehicle::STATUSES)],
        ]);

        $vehicles = Vehicle::with('assignedDriver')
            ->when($request->filled('q'), function ($query) use ($request) {
                $q = $request->input('q');
                $query->where(fn ($w) => $w
                    ->where('plate_number', 'like', "%{$q}%")
                    ->orWhere('make', 'like', "%{$q}%")
                    ->orWhere('model', 'like', "%{$q}%"));
            })
            ->when($request->filled('type'), fn ($q) => $q->where('type', $request->input('type')))
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->input('status')))
            ->latest()
            ->paginate(15)
            ->withQueryString();

        $types = Vehicle::query()->select('type')->distinct()->orderBy('type')->pluck('type');

        $drivers = User::drivers()
            ->where('agency_id', auth()->user()->agency_id)
            ->where('status', User::STATUS_ACTIVE)
            ->orderBy('name')
            ->get();

        return view('vehicles', compact('vehicles', 'types', 'drivers'));
    }

    public function store(VehicleRequest $request): RedirectResponse
    {
        Vehicle::create($request->validated());

        return back()->with('status', 'Vehicle added successfully.');
    }

    public function update(VehicleRequest $request, Vehicle $vehicle): RedirectResponse
    {
        $vehicle->update($request->validated());

        return back()->with('status', 'Vehicle updated successfully.');
    }

    public function updateStatus(VehicleStatusRequest $request, Vehicle $vehicle): RedirectResponse
    {
        $vehicle->update(['status' => $request->validated('status')]);

        return back()->with('status', 'Vehicle status updated.');
    }
}
