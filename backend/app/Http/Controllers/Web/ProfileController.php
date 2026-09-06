<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreAdminRequest;
use App\Http\Requests\UpdateProfileRequest;
use App\Models\User;
use App\Services\NotificationDispatcher;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

/**
 * Profile page (FR-04) — the Blade twin of PATCH /api/v1/me/profile.
 *
 * An administrator edits their OWN name, email and password, with no approval
 * step and no notification. Agency details are shown read-only: design
 * decision 7 excludes an agency-info editing feature because no functional
 * requirement backs one, so the prototype's editable agency inputs are
 * rendered disabled rather than dropped — the layout stays identical and the
 * information is still there to read.
 *
 * The same UpdateProfileRequest as the API, so the two surfaces validate
 * identically and a rule can never be tightened on one and not the other.
 */
class ProfileController extends Controller
{
    public function edit(Request $request): View
    {
        return view('profile', [
            'user' => $request->user()->load('agency'),
            // The agency's administrators, for the "Agency Administrators" section
            // (design decision 6 revised, 2026-09). Own agency only.
            'admins' => User::query()
                ->where('agency_id', $request->user()->agency_id)
                ->where('role', User::ROLE_ADMIN)
                ->where('status', User::STATUS_ACTIVE)
                ->orderBy('name')
                ->get(),
        ]);
    }

    /**
     * Create another administrator for the acting admin's own agency (2026-09,
     * design decision 6 revised).
     *
     * Provisioning stays "within the system" (Ch1): the agency is forced to the
     * actor's own, the actor confirms their own password (StoreAdminRequest),
     * and the agency's other administrators are notified. The server command
     * rvms:create-admin remains the fallback for when nobody can sign in.
     */
    public function storeAdmin(StoreAdminRequest $request): RedirectResponse
    {
        $admin = User::create([
            'agency_id' => $request->user()->agency_id, // forced — never taken from input
            'role' => User::ROLE_ADMIN,
            'status' => User::STATUS_ACTIVE,
            'name' => $request->validated('admin_name'),
            'email' => $request->validated('admin_email'),
            'password' => $request->validated('admin_password'),
        ]);

        app(NotificationDispatcher::class)->adminAdded($admin, $request->user());

        return redirect()->route('profile')
            ->with('status', "Administrator {$admin->name} was created. Give them the password directly.");
    }

    public function update(UpdateProfileRequest $request): RedirectResponse
    {
        $user = $request->user();

        $user->fill($request->only('name', 'email'));

        $passwordChanged = $request->filled('password');

        if ($passwordChanged) {
            $user->password = $request->input('password');
        }

        $user->save();

        // Changing your own password invalidates the session's remembered
        // hash, so Laravel would sign you out on the next request with no
        // explanation. Re-issue it instead: FR-04 is a self-service edit, not
        // a sign-out, and being ejected mid-session reads as a failure.
        if ($passwordChanged) {
            Auth::guard('web')->logoutOtherDevices($request->input('password'));
            $request->session()->put('password_hash_web', $user->getAuthPassword());
        }

        return back()->with('status', $passwordChanged
            ? 'Profile updated. Your new password is now in use.'
            : 'Profile updated.');
    }

}
