<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;

/**
 * An agency's administrators — a read-only directory.
 *
 * Deliberately NOT a management module. Administrator accounts are provisioned
 * (design decision 6) — there is no create, no edit and no delete here, and
 * adding them would invent a feature no requirement asks for. The list is
 * read-only: one administrator cannot reset another's password (FR-22, narrowed
 * 2026-08), so an administrator who is locked out is restored by the personnel
 * maintaining the server, using `php artisan rvms:reset-password`.
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
}
