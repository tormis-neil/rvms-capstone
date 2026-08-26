<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateProfileRequest;
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
        ]);
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
