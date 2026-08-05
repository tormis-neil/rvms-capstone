<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\ResetAdminPasswordRequest;
use App\Models\User;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * An agency's administrators, and the one action they can take on each other
 * (FR-04a, 2026-08).
 *
 * Deliberately NOT a management module. Administrator accounts are provisioned
 * (design decision 6) — there is no create, no edit and no delete here, and
 * adding them would invent a feature no requirement asks for. The list exists
 * only so that a colleague can be found, and the single write is a password
 * reset, which is what stops a forgotten password from locking an agency out of
 * its own dashboard.
 *
 * Everything is scoped to the caller's own agency, and a foreign or non-admin
 * id 404s rather than 403s — the same rule as every other lookup in the API, so
 * a rejection never confirms that an account exists.
 */
class AdminController extends Controller
{
    /** The caller's colleagues — never themselves, never another agency. */
    public function index(Request $request)
    {
        $colleagues = User::query()
            ->where('agency_id', $request->user()->agency_id)
            ->where('role', User::ROLE_ADMIN)
            ->whereKeyNot($request->user()->id)
            ->orderBy('name')
            ->get(['id', 'name', 'email', 'status']);

        return response()->json(['data' => $colleagues]);
    }

    public function resetPassword(ResetAdminPasswordRequest $request, User $admin)
    {
        $this->authorizeColleague($request, $admin);

        $admin->update(['password' => $request->validated('password')]);
        $admin->tokens()->delete();

        return response()->json(['data' => ['reset' => true]]);
    }

    /**
     * Same agency, actually an administrator, and not the caller.
     *
     * Self is excluded because resetting your own password is FR-04's job on
     * the profile page. Routing it through here would let an admin skip the
     * current-password confirmation this endpoint demands.
     */
    private function authorizeColleague(Request $request, User $admin): void
    {
        if ($admin->role !== User::ROLE_ADMIN
            || $admin->agency_id !== $request->user()->agency_id
            || $admin->id === $request->user()->id) {
            throw new NotFoundHttpException;
        }
    }
}
