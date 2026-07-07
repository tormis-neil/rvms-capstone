<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Http\Requests\DriverRequest;
use App\Models\User;
use App\Models\Vehicle;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * Admin dashboard Drivers page + access-request approval (FR-03, FR-06)
 * with license expiry monitoring surface (FR-08).
 * User has no global scope, so queries are explicitly agency-scoped.
 */
class DriverController extends Controller
{
    public function index(Request $request): View
    {
        $request->validate([
            'q' => ['sometimes', 'nullable', 'string', 'max:100'],
            'license_status' => ['sometimes', 'nullable', Rule::in(['Valid', 'Expiring Soon', 'Expired'])],
        ]);

        $agency = auth()->user()->agency;
        $warningDays = $agency->license_expiry_warning_days;

        $base = fn () => User::drivers()->where('agency_id', $agency->id);

        // License monitoring summary (FR-08), driven by the agency threshold.
        $activeDrivers = fn () => $base()->where('status', User::STATUS_ACTIVE);
        $expiringSoonCount = $activeDrivers()->expiringSoon($warningDays)->count();
        $expiredCount = $activeDrivers()->expired()->count();
        $validCount = $activeDrivers()
            ->whereNotNull('license_expiry_date')
            ->count() - $expiringSoonCount - $expiredCount;

        $drivers = $base()
            ->with('assignedVehicles')
            ->where('status', '!=', User::STATUS_PENDING)
            ->when($request->filled('q'), function ($query) use ($request) {
                $q = $request->input('q');
                $query->where(fn ($w) => $w
                    ->where('name', 'like', "%{$q}%")
                    ->orWhere('email', 'like', "%{$q}%")
                    ->orWhere('license_number', 'like', "%{$q}%"));
            })
            ->when($request->filled('license_status'), function ($query) use ($request, $warningDays) {
                match ($request->input('license_status')) {
                    'Expired' => $query->expired(),
                    'Expiring Soon' => $query->expiringSoon($warningDays),
                    'Valid' => $query->whereNotNull('license_expiry_date')
                        ->whereDate('license_expiry_date', '>', now()->addDays($warningDays)->toDateString()),
                };
            })
            ->orderBy('name')
            ->paginate(15)
            ->withQueryString();

        $pending = $base()
            ->where('status', User::STATUS_PENDING)
            ->latest()
            ->get();

        $vehicles = Vehicle::orderBy('plate_number')->get();

        return view('drivers', compact(
            'drivers', 'pending', 'vehicles', 'warningDays',
            'validCount', 'expiringSoonCount', 'expiredCount',
        ));
    }

    public function store(DriverRequest $request): RedirectResponse
    {
        $driver = User::create($request->safe()->except('vehicle_id') + [
            'agency_id' => auth()->user()->agency_id,
            'role' => User::ROLE_DRIVER,
            'status' => User::STATUS_ACTIVE,
        ]);

        $this->assignVehicleIfFree($driver, $request->input('vehicle_id'));

        return back()->with('status', 'Driver registered successfully.');
    }

    public function update(DriverRequest $request, User $driver): RedirectResponse
    {
        $this->authorizeDriver($driver);

        $data = $request->safe()->except(['password', 'vehicle_id']);
        if ($request->filled('password')) {
            $data['password'] = $request->input('password');
        }
        $driver->update($data);

        $this->assignVehicleIfFree($driver, $request->input('vehicle_id'));

        return back()->with('status', 'Driver updated successfully.');
    }

    public function approve(User $driver): RedirectResponse
    {
        $this->authorizeDriver($driver);
        $driver->update(['status' => User::STATUS_ACTIVE]);

        return back()->with('status', 'Driver access request approved.');
    }

    public function reject(User $driver): RedirectResponse
    {
        $this->authorizeDriver($driver);
        $driver->update(['status' => User::STATUS_REJECTED]);

        return back()->with('status', 'Driver access request rejected.');
    }

    /**
     * Assign the chosen vehicle to this driver if it is currently free.
     * A driver may hold more than one vehicle (Ch4 ERD); each vehicle has
     * at most one primary driver, so an already-assigned vehicle is never
     * silently taken over — reassignment/unassignment is managed from the
     * Vehicles page. Vehicle's global scope keeps this within the agency.
     */
    private function assignVehicleIfFree(User $driver, mixed $vehicleId): void
    {
        if (! $vehicleId) {
            return; // "No change" — unassignment happens on the Vehicles page
        }

        Vehicle::whereKey((int) $vehicleId)
            ->whereNull('assigned_driver_id')
            ->update(['assigned_driver_id' => $driver->id]);
    }

    private function authorizeDriver(User $driver): void
    {
        abort_unless(
            $driver->isDriver() && $driver->agency_id === auth()->user()->agency_id,
            404,
        );
    }
}
