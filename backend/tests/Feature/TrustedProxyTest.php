<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Route;
use Tests\TestCase;

/**
 * The app has to be reachable through a reverse proxy, not only on localhost.
 *
 * Remote user-acceptance testing runs the dashboard through a Cloudflare tunnel:
 * the client's browser speaks HTTPS to Cloudflare, and Cloudflare speaks plain
 * HTTP to `php artisan serve` on the laptop. Unless the X-Forwarded-* headers are
 * trusted, Laravel concludes the request arrived over HTTP and generates every
 * asset link, form action and redirect as http:// inside an https:// page. The
 * browser blocks those as mixed content, so the dashboard loads with no
 * stylesheet and no working login — a failure that never appears in local
 * testing, because on localhost there is no proxy to be wrong about.
 *
 * These tests pin the two things that break: the scheme, and the host.
 */
class TrustedProxyTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Route::get('/__proxy-probe', fn () => response()->json([
            'secure' => request()->isSecure(),
            'root' => url('/'),
            'asset' => asset('assets/css/style.css'),
        ]));
    }

    public function test_a_request_forwarded_over_https_is_treated_as_secure(): void
    {
        $this->get('/__proxy-probe', ['X-Forwarded-Proto' => 'https'])
            ->assertOk()
            ->assertJson(['secure' => true]);
    }

    public function test_generated_urls_use_the_proxy_scheme_and_host(): void
    {
        $response = $this->get('/__proxy-probe', [
            'X-Forwarded-Proto' => 'https',
            'X-Forwarded-Host' => 'rvms-uat.trycloudflare.com',
        ]);

        $response->assertOk();

        // The whole point: no http:// asset on an https:// page.
        $this->assertStringStartsWith('https://rvms-uat.trycloudflare.com', $response->json('root'));
        $this->assertStringStartsWith('https://rvms-uat.trycloudflare.com', $response->json('asset'));
    }

    public function test_a_plain_local_request_is_unaffected(): void
    {
        // No proxy in front means no forwarded headers, so nothing changes for
        // the USB and hotspot demo tiers, which serve over plain HTTP.
        $this->get('/__proxy-probe')
            ->assertOk()
            ->assertJson(['secure' => false]);
    }
}
