<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Services\MaintenanceAlerts;
use App\Http\Requests\StoreDriverRequest;
use App\Http\Requests\UpdateDriverRequest;
use App\Models\User;
use App\Models\Vehicle;
use App\Services\NotificationDispatcher;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Drivers dashboard page (FR-03, FR-06, FR-08) — the Blade twin of the
 * /api/v1/drivers + /api/v1/licenses/monitoring endpoints.
 */
class DriverController extends Controller
{
    public function index(Request $request): View
    {
        $agencyId = $request->user()->agency_id;

        $drivers = User::query()
            ->drivers()
            ->where('agency_id', $agencyId)
            ->where('status', User::STATUS_ACTIVE)
            ->when($request->filled('search'), function ($query) use ($request) {
                $term = '%'.$request->string('search').'%';
                $query->where(fn ($q) => $q
                    ->where('name', 'like', $term)
                    ->orWhere('license_number', 'like', $term));
            })
            ->when($request->filled('license_status'), fn ($q) => $q->whereIn('id', $this->driverIdsWithLicenseStatus($agencyId, $request->string('license_status'))))
            // 'agency' too: licenseStatus() reads the agency's warning window,
            // so without it every table row costs one extra query (R10.4 N+1).
            ->with(['vehicles', 'agency'])
            ->orderBy('name')
            ->paginate(10)
            ->withQueryString();

        // Documented addition (FR-03): pending self-registrations awaiting approval.
        $pendingDrivers = User::query()
            ->drivers()
            ->where('agency_id', $agencyId)
            ->where('status', User::STATUS_PENDING)
            ->orderBy('created_at')
            ->get();

        $allDrivers = User::query()->drivers()->with('agency')->where('agency_id', $agencyId)->where('status', User::STATUS_ACTIVE)->get();
        $licenseCounts = ['Valid' => 0, 'Expiring Soon' => 0, 'Expired' => 0];
        foreach ($allDrivers as $driver) {
            if ($status = $driver->licenseStatus()) {
                $licenseCounts[$status]++;
            }
        }

        // Vehicles selectable in the Add/Edit forms: unassigned ones, plus the
        // vehicle(s) already assigned to the driver being edited (added client-side
        // per row) — the select can never "steal" another driver's vehicle.
        $availableVehicles = Vehicle::query()
            ->whereNull('assigned_driver_id')
            ->orderBy('plate_number')
            ->get(['id', 'plate_number', 'type']);

        // Label lookup for ALL agency vehicles (incl. already-assigned ones), so the
        // Edit modal's script can show a driver's own current vehicle(s) as options
        // even though they're excluded from $availableVehicles above.
        $vehicleLabels = Vehicle::query()
            ->get(['id', 'plate_number', 'type'])
            ->mapWithKeys(fn (Vehicle $v) => [(string) $v->id => "{$v->plate_number} ({$v->type})"]);

        return view('drivers', [
            'drivers' => $drivers,
            'pendingDrivers' => $pendingDrivers,
            'licenseCounts' => $licenseCounts,
            'availableVehicles' => $availableVehicles,
            'vehicleLabels' => $vehicleLabels,
        ]);
    }

    public function store(StoreDriverRequest $request): RedirectResponse
    {
        $driver = User::create([
            'agency_id' => $request->user()->agency_id,
            'role' => User::ROLE_DRIVER,
            'status' => User::STATUS_ACTIVE,
            'name' => $request->validated('name'),
            'email' => $request->validated('email'),
            'password' => $request->validated('password'),
            'license_number' => $request->validated('license_number'),
            'license_expiry_date' => $request->validated('license_expiry_date'),
        ]);

        if ($vehicleId = $request->validated('assigned_vehicle_id')) {
            Vehicle::whereKey($vehicleId)->update(['assigned_driver_id' => $driver->id]);
        }

        // A licence can be recorded ALREADY inside the warning window, or
        // already expired. Alert now rather than waiting for tomorrow's sweep;
        // rvms:license-alerts shares this method, so neither path double-alerts.
        app(MaintenanceAlerts::class)->raiseForDriver($driver);

        return redirect()->route('drivers')->with('status', 'Driver registered successfully.');
    }

    public function update(UpdateDriverRequest $request, User $driver): RedirectResponse
    {
        $this->authorizeDriver($request, $driver);

        $data = $request->safe()->only(['name', 'email', 'license_number', 'license_expiry_date']);

        if ($password = $request->validated('password')) {
            $data['password'] = $password;
        }

        $driver->update($data);

        if ($vehicleId = $request->validated('assigned_vehicle_id')) {
            Vehicle::whereKey($vehicleId)->update(['assigned_driver_id' => $driver->id]);
        }

        app(MaintenanceAlerts::class)->raiseForDriver($driver->fresh());

        return redirect()->route('drivers')->with('status', 'Driver details updated.');
    }

    public function approve(Request $request, User $driver): RedirectResponse
    {
        $this->authorizeDriver($request, $driver);

        $driver->update(['status' => User::STATUS_ACTIVE]);

        return redirect()->route('drivers')->with('status', $driver->name.' was approved.');
    }

    public function reject(Request $request, User $driver): RedirectResponse
    {
        $this->authorizeDriver($request, $driver);

        $driver->update(['status' => User::STATUS_REJECTED]);

        return redirect()->route('drivers')->with('status', $driver->name.' was rejected.');
    }

    /**
     * Set a new password for a driver (FR-22, 2026-08).
     *
     * The action behind "contact your administrator" on the login screen. The
     * admin types the new password and reads it out, after confirming their own
     * — taking over someone else's sign-in should not be possible from a
     * dashboard someone walked away from.
     */
    public function resetPassword(Request $request, User $driver)
    {
        $this->authorizeDriver($request, $driver);

        // Same rule as the API path (FR-22, 2026-08): the acting administrator
        // confirms who they are before taking over someone else's sign-in.
        // Validated here rather than through ResetDriverPasswordRequest because
        // this action predates it and returns a redirect, not JSON.
        $validated = $request->validate([
            'current_password' => ['required', 'string'],
            'password' => ['required', 'string', 'min:8'],
        ], [
            'current_password.required' => 'Confirm your own password before resetting a driver’s.',
        ]);

        if (! Hash::check($validated['current_password'], (string) $request->user()->password)) {
            return back()->withErrors(['current_password' => 'That is not your current password.']);
        }

        $driver->update(['password' => $validated['password']]);
        $driver->tokens()->delete();

        app(NotificationDispatcher::class)->passwordWasReset($driver, $request->user());

        return back(fallback: route('drivers'))
            ->with('status', "Password reset for {$driver->name}. Give them the new password directly.");
    }

    private function authorizeDriver(Request $request, User $driver): void
    {
        if ($driver->role !== User::ROLE_DRIVER || $driver->agency_id !== $request->user()->agency_id) {
            throw new NotFoundHttpException;
        }
    }

    private function driverIdsWithLicenseStatus(int $agencyId, string $status): array
    {
        return User::query()
            ->drivers()
            ->where('agency_id', $agencyId)
            ->get()
            ->filter(fn (User $driver) => $driver->licenseStatus() === $status)
            ->pluck('id')
            ->all();
    }
}
