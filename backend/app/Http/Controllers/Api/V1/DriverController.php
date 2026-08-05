<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Services\MaintenanceAlerts;
use App\Services\RecordDeletion;
use App\Http\Requests\StoreDriverRequest;
use App\Http\Requests\UpdateDriverLicenseRequest;
use App\Http\Requests\UpdateDriverRequest;
use App\Http\Resources\DriverResource;
use App\Models\User;
use App\Models\Vehicle;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Admin driver records API (FR-03, FR-06, FR-08). The User model carries no
 * AgencyScope (login must look users up by email across agencies), so every
 * query and every route-bound $driver here is manually agency-checked.
 */
class DriverController extends Controller
{
    public function index(Request $request)
    {
        $drivers = User::query()
            ->drivers()
            ->where('agency_id', $request->user()->agency_id)
            ->where('status', $request->input('status', User::STATUS_ACTIVE))
            ->when($request->filled('search'), function ($query) use ($request) {
                $term = '%'.$request->string('search').'%';
                $query->where(fn ($q) => $q
                    ->where('name', 'like', $term)
                    ->orWhere('license_number', 'like', $term));
            })
            ->with('vehicles')
            ->orderBy('name')
            ->paginate(10);

        return DriverResource::collection($drivers);
    }

    public function store(StoreDriverRequest $request)
    {
        $driver = User::create([
            'agency_id' => $request->user()->agency_id,
            'role' => User::ROLE_DRIVER,
            'status' => User::STATUS_ACTIVE, // admin-added drivers are active immediately (FR-03)
            'name' => $request->validated('name'),
            'email' => $request->validated('email'),
            'password' => $request->validated('password'),
            'license_number' => $request->validated('license_number'),
            'license_expiry_date' => $request->validated('license_expiry_date'),
        ]);

        if ($vehicleId = $request->validated('assigned_vehicle_id')) {
            Vehicle::whereKey($vehicleId)->update(['assigned_driver_id' => $driver->id]);
        }

        // A licence can be recorded ALREADY inside the warning window (FR-08).
        app(MaintenanceAlerts::class)->raiseForDriver($driver);

        return DriverResource::make($driver->load('vehicles'))->response()->setStatusCode(201);
    }

    public function show(Request $request, User $driver)
    {
        $this->authorizeDriver($request, $driver);

        return DriverResource::make($driver->load('vehicles'));
    }

    public function update(UpdateDriverRequest $request, User $driver)
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

        return DriverResource::make($driver->fresh()->load('vehicles'));
    }

    public function approve(Request $request, User $driver)
    {
        $this->authorizeDriver($request, $driver);

        $driver->update(['status' => User::STATUS_ACTIVE]);

        return DriverResource::make($driver->fresh());
    }

    public function reject(Request $request, User $driver)
    {
        $this->authorizeDriver($request, $driver);

        $driver->update(['status' => User::STATUS_REJECTED]);

        return DriverResource::make($driver->fresh());
    }

    public function updateLicense(UpdateDriverLicenseRequest $request, User $driver)
    {
        $this->authorizeDriver($request, $driver);

        $driver->update(['license_expiry_date' => $request->validated('license_expiry_date')]);

        // A "renewal" may still land inside the warning window.
        app(MaintenanceAlerts::class)->raiseForDriver($driver->fresh());

        return DriverResource::make($driver->fresh());
    }

    /**
     * Soft-delete a driver (FR-06, extended 2026-08).
     *
     * Their vehicle assignments are released; their inspections and damage
     * reports keep their name. Refused (422) while they are out on an active
     * dispatch.
     */
    public function destroy(Request $request, User $driver)
    {
        $this->authorizeDriver($request, $driver);

        app(RecordDeletion::class)->deleteDriver($driver);

        return response()->json(['data' => ['deleted' => true]]);
    }

    /** Bring a deleted driver back. Vehicle assignments are not restored. */
    public function restore(Request $request, int $driver)
    {
        $trashed = User::onlyTrashed()->find($driver);

        // Same 404-not-403 rule as every other lookup here: a foreign or
        // non-driver id must not be distinguishable from one that never existed.
        if (! $trashed || $trashed->role !== User::ROLE_DRIVER
            || $trashed->agency_id !== $request->user()->agency_id) {
            throw new NotFoundHttpException;
        }

        app(RecordDeletion::class)->restoreDriver($trashed);

        return DriverResource::make($trashed->fresh());
    }

    private function authorizeDriver(Request $request, User $driver): void
    {
        if ($driver->role !== User::ROLE_DRIVER || $driver->agency_id !== $request->user()->agency_id) {
            throw new NotFoundHttpException;
        }
    }
}
