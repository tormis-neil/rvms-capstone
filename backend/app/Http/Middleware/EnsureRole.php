<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Gate a route to one or more roles (FR-02), e.g. ->middleware('role:admin').
 * JSON requests get status codes; browser requests are redirected/aborted.
 */
class EnsureRole
{
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();

        if ($user === null) {
            return $request->expectsJson()
                ? response()->json(['message' => 'Unauthenticated.'], 401)
                : redirect()->guest(route('login'));
        }

        if (! in_array($user->role, $roles, true)) {
            if ($request->expectsJson()) {
                return response()->json(['message' => 'You do not have permission to access this resource.'], 403);
            }

            abort(403, 'You do not have permission to access this page.');
        }

        return $next($request);
    }
}
