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

    /**
     * Set the agency's licence warning window (FR-08, 2026-08).
     *
     * The Chapter 4 data dictionary has always described
     * `license_expiry_warning_days` as "configurable per agency", and it was
     * stored as a column precisely so it would not be a constant — but nothing
     * ever wrote to it, so every agency sat on the seeded default of 30 forever
     * and the claim was untrue.
     *
     * This is NOT the agency-info editing that design decision 7 excludes. That
     * decision is about the agency's IDENTITY — name, location, contact — which
     * no requirement allows editing and which stays display-only above. This is
     * an operational threshold that FR-08 itself depends on.
     *
     * The change takes effect immediately everywhere: licence status is derived
     * on read from this column rather than stored, so the Drivers page, the
     * summary cards, the monitoring endpoint and tomorrow's sweep all move
     * together.
     */
    public function updateLicenseWindow(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            // At least a day, because a window of zero would mean a licence is
            // never "Expiring Soon" — it would go straight from Valid to
            // Expired, which defeats FR-08. A year is the sensible ceiling: a
            // window longer than the renewal cycle flags every licence forever.
            'license_expiry_warning_days' => ['required', 'integer', 'min:1', 'max:365'],
        ], [
            'license_expiry_warning_days.min' => 'The warning window must be at least 1 day, '
                .'otherwise a licence would go straight from Valid to Expired with no warning.',
            'license_expiry_warning_days.max' => 'The warning window cannot exceed 365 days.',
        ]);

        $request->user()->agency->update($validated);

        return back()->with('status', sprintf(
            'Licences will now be flagged as expiring %d days before they lapse.',
            $validated['license_expiry_warning_days'],
        ));
    }
}
