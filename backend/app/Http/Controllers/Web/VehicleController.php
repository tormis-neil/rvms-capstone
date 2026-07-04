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
    public function index(): View
    {
        $vehicles = Vehicle::with('assignedDriver')->latest()->paginate(15);

        $drivers = User::drivers()
            ->where('agency_id', auth()->user()->agency_id)
            ->where('status', User::STATUS_ACTIVE)
            ->orderBy('name')
            ->get();

        return view('vehicles', compact('vehicles', 'drivers'));
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
