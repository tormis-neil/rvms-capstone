<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Keeps signed-in dashboard pages out of the browser's back/forward cache.
 *
 * Without this the browser answers the Back button from a snapshot it took
 * earlier instead of re-asking the server, so a page can show a vehicle status
 * the database no longer holds — the admin changed it, went Back, and read the
 * old value off a stale copy. It also means the pages stay recoverable after
 * sign-out: pressing Back on a shared laptop would redisplay the previous
 * admin's agency records without logging in (NFR-02).
 *
 * `no-store` is the directive that suppresses that snapshot; the rest are for
 * older browsers and intermediate caches.
 */
class NoStoreDashboard
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        $response->headers->set('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0');
        $response->headers->set('Pragma', 'no-cache');
        $response->headers->set('Expires', '0');

        return $response;
    }
}
