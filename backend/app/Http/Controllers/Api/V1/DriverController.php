<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\DriverRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Validation\Rule;

/**
 * Driver record management + access-request approval (FR-03, FR-06).
 * Drivers are users with role=driver. The User model has no global
 * agency scope, so every query here is explicitly agency-scoped.
 */
class DriverController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $request->validate([
            'status' => ['sometimes', Rule::in([
                User::STATUS_PENDING, User::STATUS_ACTIVE, User::STATUS_REJECTED,
            ])],
        ]);

        $drivers = $this->agencyDrivers($request)
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->input('status')))
            ->latest()
            ->paginate(15);

        return UserResource::collection($drivers);
    }

    public function store(DriverRequest $request): JsonResponse
    {
        $driver = User::create($request->safe()->merge([
            'agency_id' => $request->user()->agency_id,
            'role' => User::ROLE_DRIVER,
            'status' => User::STATUS_ACTIVE, // admin-added drivers are active immediately
        ])->all());

        return (new UserResource($driver))->response()->setStatusCode(201);
    }

    public function show(Request $request, User $driver): UserResource
    {
        $this->authorizeDriver($request, $driver);

        return new UserResource($driver);
    }

    public function update(DriverRequest $request, User $driver): UserResource
    {
        $this->authorizeDriver($request, $driver);

        $data = $request->safe()->except('password');
        if ($request->filled('password')) {
            $data['password'] = $request->input('password');
        }
        $driver->update($data);

        return new UserResource($driver);
    }

    /**
     * PATCH /drivers/{id}/approve — approve a pending self-registration (FR-03).
     */
    public function approve(Request $request, User $driver): UserResource
    {
        $this->authorizeDriver($request, $driver);
        $driver->update(['status' => User::STATUS_ACTIVE]);

        return new UserResource($driver);
    }

    /**
     * PATCH /drivers/{id}/reject — reject a pending self-registration (FR-03).
     */
    public function reject(Request $request, User $driver): UserResource
    {
        $this->authorizeDriver($request, $driver);
        $driver->update(['status' => User::STATUS_REJECTED]);

        return new UserResource($driver);
    }

    /**
     * Base query: only drivers belonging to the caller's agency.
     */
    private function agencyDrivers(Request $request)
    {
        return User::where('role', User::ROLE_DRIVER)
            ->where('agency_id', $request->user()->agency_id);
    }

    /**
     * Enforce that the target is a driver in the caller's own agency.
     * Anything else (admin, or another agency's driver) → 404.
     */
    private function authorizeDriver(Request $request, User $driver): void
    {
        abort_unless(
            $driver->isDriver() && $driver->agency_id === $request->user()->agency_id,
            404,
        );
    }
}
