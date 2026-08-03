<?php

namespace Tests\Feature;

use App\Models\Agency;
use App\Models\User;
use App\Support\LoginThrottle;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

/**
 * R10 sub-task 5 — login throttling (NFR-02).
 *
 * Password guessing is the one attack that needs no vulnerability at all, and
 * the seeded accounts all share a demo password. Both front doors open the
 * same accounts, so both are throttled by the same class.
 *
 * The key is email+IP, not IP alone: four agencies may share one office NAT,
 * and an IP-only key would let one administrator's mistyped password lock out
 * their colleagues — a denial of service delivered by accident.
 */
class LoginThrottleTest extends TestCase
{
    use RefreshDatabase;

    private Agency $agency;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        RateLimiter::clear('');
        $this->agency = Agency::factory()->create(['code' => 'BFP']);
        $this->admin = User::factory()->admin()->create([
            'agency_id' => $this->agency->id,
            'email' => 'admin@rvms.local',
            'password' => 'password',
        ]);
    }

    private function failWebLogin(string $email = 'admin@rvms.local'): TestResponse
    {
        return $this->from('/login')->post('/login', ['email' => $email, 'password' => 'wrong-password']);
    }

    /* --------------------------------- web -------------------------------- */

    public function test_the_dashboard_login_locks_out_after_repeated_failures(): void
    {
        for ($i = 0; $i < LoginThrottle::MAX_ATTEMPTS; $i++) {
            $this->failWebLogin()->assertSessionHasErrors('email');
        }

        // The next attempt is refused before the password is even compared.
        $this->failWebLogin()
            ->assertSessionHasErrors('email');

        $this->assertStringContainsString(
            'Too many login attempts',
            session('errors')->first('email'),
        );
    }

    /** Even the CORRECT password is refused while the lockout stands. */
    public function test_a_locked_out_account_is_refused_the_right_password_too(): void
    {
        for ($i = 0; $i < LoginThrottle::MAX_ATTEMPTS; $i++) {
            $this->failWebLogin();
        }

        $this->from('/login')
            ->post('/login', ['email' => 'admin@rvms.local', 'password' => 'password'])
            ->assertSessionHasErrors('email');

        $this->assertGuest();
        $this->assertStringContainsString('Too many login attempts', session('errors')->first('email'));
    }

    /** A success before the limit clears the counter — typos are forgiven. */
    public function test_a_successful_login_resets_the_counter(): void
    {
        $this->failWebLogin();
        $this->failWebLogin();

        $this->post('/login', ['email' => 'admin@rvms.local', 'password' => 'password'])
            ->assertRedirect(route('dashboard'));
        $this->post('/logout');

        // Back to a full allowance: MAX_ATTEMPTS more failures are needed.
        for ($i = 0; $i < LoginThrottle::MAX_ATTEMPTS; $i++) {
            $this->failWebLogin()->assertSessionHasErrors('email');
            $this->assertStringNotContainsString(
                'Too many login attempts',
                session('errors')->first('email'),
                "Locked out after only {$i} post-success failures — the counter did not reset.",
            );
        }
    }

    /**
     * The reason the key is not IP-only: colleagues behind one office NAT must
     * not be able to lock each other out.
     */
    public function test_one_accounts_lockout_does_not_lock_out_a_colleague(): void
    {
        $colleague = User::factory()->admin()->create([
            'agency_id' => $this->agency->id,
            'email' => 'admin2@rvms.local',
            'password' => 'password',
        ]);

        for ($i = 0; $i < LoginThrottle::MAX_ATTEMPTS + 1; $i++) {
            $this->failWebLogin('admin@rvms.local');
        }

        // Same IP, different account — signs in normally.
        $this->post('/login', ['email' => 'admin2@rvms.local', 'password' => 'password'])
            ->assertRedirect(route('dashboard'));
        $this->assertAuthenticatedAs($colleague);
    }

    /* --------------------------------- api -------------------------------- */

    public function test_the_api_login_returns_429_after_repeated_failures(): void
    {
        $driver = User::factory()->driver()->create([
            'agency_id' => $this->agency->id,
            'email' => 'driver@rvms.local',
            'password' => 'password',
        ]);

        for ($i = 0; $i < LoginThrottle::MAX_ATTEMPTS; $i++) {
            $this->postJson('/api/v1/login', [
                'email' => $driver->email, 'password' => 'wrong-password',
            ])->assertStatus(422);
        }

        $this->postJson('/api/v1/login', [
            'email' => $driver->email, 'password' => 'wrong-password',
        ])->assertStatus(429);
    }

    /** A pending account retrying forever is still traffic worth limiting. */
    public function test_a_refused_non_active_account_also_counts_towards_the_limit(): void
    {
        $pending = User::factory()->driver()->create([
            'agency_id' => $this->agency->id,
            'email' => 'pending@rvms.local',
            'password' => 'password',
            'status' => User::STATUS_PENDING,
        ]);

        for ($i = 0; $i < LoginThrottle::MAX_ATTEMPTS; $i++) {
            $this->postJson('/api/v1/login', [
                'email' => $pending->email, 'password' => 'password',
            ])->assertStatus(403);
        }

        $this->postJson('/api/v1/login', [
            'email' => $pending->email, 'password' => 'password',
        ])->assertStatus(429);
    }

    /** The web and API counters are the same counter — one door, one lock. */
    public function test_the_web_and_api_share_one_limit(): void
    {
        for ($i = 0; $i < LoginThrottle::MAX_ATTEMPTS; $i++) {
            $this->failWebLogin('admin@rvms.local');
        }

        $this->postJson('/api/v1/login', [
            'email' => 'admin@rvms.local', 'password' => 'password',
        ])->assertStatus(429);
    }

    /** The store must never hold a readable address. */
    public function test_the_rate_limit_key_does_not_contain_the_email(): void
    {
        $this->failWebLogin('admin@rvms.local');

        $reflection = new \ReflectionMethod(LoginThrottle::class, 'key');
        $key = $reflection->invoke(null, request()->merge([]), 'admin@rvms.local');

        $this->assertStringNotContainsString('admin@rvms.local', $key);
        $this->assertStringStartsWith('login:', $key);
    }

    /* ------------------------------- reports ------------------------------ */

    /** Reports are unpaginated by design, so the endpoint is capped. */
    public function test_report_generation_is_rate_limited(): void
    {
        $this->actingAs($this->admin, 'sanctum');

        for ($i = 0; $i < 20; $i++) {
            $this->getJson('/api/v1/reports/vehicle-status')->assertOk();
        }

        $this->getJson('/api/v1/reports/vehicle-status')->assertStatus(429);
    }
}
