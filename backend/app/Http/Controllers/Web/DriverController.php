<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreDriverRequest;
use App\Http\Requests\UpdateDriverLicenseRequest;
use App\Http\Requests\UpdateDriverRequest;
use App\Models\User;
use App\Models\Vehicle;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
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
            ->with('vehicles')
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

        $allDrivers = User::query()->drivers()->where('agency_id', $agencyId)->where('status', User::STATUS_ACTIVE)->get();
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

        return redirect()->route('drivers')->with('status', 'Driver details updated.');
    }

    public function updateLicense(UpdateDriverLicenseRequest $request, User $driver): RedirectResponse
    {
        $this->authorizeDriver($request, $driver);

        $driver->update(['license_expiry_date' => $request->validated('license_expiry_date')]);

        return redirect()->route('drivers')->with('status', 'License renewal recorded.');
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
