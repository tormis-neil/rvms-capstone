<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Http\Requests\DriverRequest;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

/**
 * Admin dashboard Drivers page + access-request approval (FR-03, FR-06).
 * User has no global scope, so queries are explicitly agency-scoped.
 */
class DriverController extends Controller
{
    public function index(): View
    {
        $agencyId = auth()->user()->agency_id;

        $drivers = User::drivers()
            ->where('agency_id', $agencyId)
            ->where('status', '!=', User::STATUS_PENDING)
            ->orderBy('name')
            ->paginate(15);

        $pending = User::drivers()
            ->where('agency_id', $agencyId)
            ->where('status', User::STATUS_PENDING)
            ->latest()
            ->get();

        return view('drivers', compact('drivers', 'pending'));
    }

    public function store(DriverRequest $request): RedirectResponse
    {
        User::create($request->safe()->merge([
            'agency_id' => auth()->user()->agency_id,
            'role' => User::ROLE_DRIVER,
            'status' => User::STATUS_ACTIVE,
        ])->all());

        return back()->with('status', 'Driver added successfully.');
    }

    public function update(DriverRequest $request, User $driver): RedirectResponse
    {
        $this->authorizeDriver($driver);

        $data = $request->safe()->except('password');
        if ($request->filled('password')) {
            $data['password'] = $request->input('password');
        }
        $driver->update($data);

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

    private function authorizeDriver(User $driver): void
    {
        abort_unless(
            $driver->isDriver() && $driver->agency_id === auth()->user()->agency_id,
            404,
        );
    }
}
