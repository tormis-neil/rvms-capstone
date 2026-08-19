<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->alias([
            'role' => \App\Http\Middleware\EnsureRole::class,
        ]);

        // Behind a reverse proxy (a Cloudflare tunnel for remote UAT, or any
        // hosted deployment) the browser speaks HTTPS to the proxy and the proxy
        // speaks plain HTTP to us. Without trusting its X-Forwarded-* headers
        // Laravel believes the request arrived over HTTP and builds every asset
        // and form URL as http:// on an https:// page — which the browser then
        // blocks as mixed content, so the dashboard renders with no stylesheet
        // and no working POST. Nothing changes when running on localhost, where
        // there is no proxy and no such header to read.
        $middleware->trustProxies(at: '*');

        // Already-authenticated users hitting guest pages (e.g. /login) go to the dashboard.
        $middleware->redirectUsersTo(fn () => route('dashboard'));
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();
