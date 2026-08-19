<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Support\LoginThrottle;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

/**
 * Web (session) authentication for the admin dashboard (FR-01, FR-02).
 * The dashboard is admin-only: drivers authenticate on the mobile app
 * via the /api/v1 token endpoints instead.
 */
class AuthController extends Controller
{
    public function showLogin(): View
    {
        return view('auth.login');
    }

    public function login(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'string', 'email:strict'],
            'password' => ['required', 'string'],
        ]);

        // Throttled per account+IP (R10 sub-task 5, NFR-02). Checked BEFORE
        // Auth::attempt, so a locked-out caller never reaches the password
        // comparison at all.
        LoginThrottle::assertNotLocked($request, $credentials['email']);

        if (! Auth::attempt($credentials)) {
            LoginThrottle::recordFailure($request, $credentials['email']);

            throw ValidationException::withMessages([
                'email' => 'These credentials do not match our records.',
            ]);
        }

        $user = Auth::user();

        if ($user->role !== User::ROLE_ADMIN) {
            LoginThrottle::recordFailure($request, $credentials['email']);
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            throw ValidationException::withMessages([
                'email' => 'Driver accounts sign in on the RVMS mobile app. This dashboard is for agency administrators.',
            ]);
        }

        if (! $user->isActive()) {
            LoginThrottle::recordFailure($request, $credentials['email']);
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            throw ValidationException::withMessages([
                'email' => 'This account is not active. Contact your system administrator.',
            ]);
        }

        // A correct sign-in wipes the counter: two typos then success must
        // not leave the admin part-way to a lockout.
        LoginThrottle::clear($request, $credentials['email']);

        $request->session()->regenerate();

        return redirect()->intended(route('dashboard'));
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}
