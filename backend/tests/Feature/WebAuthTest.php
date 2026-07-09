<?php

namespace Tests\Feature;

use App\Models\Agency;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * R1 — web (session) auth for the admin dashboard: admins get in, drivers
 * are pointed to the mobile app, guests are bounced to login (FR-01, FR-02).
 */
class WebAuthTest extends TestCase
{
    use RefreshDatabase;

    private function makeAdmin(array $overrides = []): User
    {
        return User::factory()->admin()->create($overrides + ['password' => 'password']);
    }

    public function test_login_page_renders_for_guests(): void
    {
        $this->get('/login')
            ->assertOk()
            ->assertSee('Sign in to your agency admin account');
    }

    public function test_admin_can_log_in_and_reach_the_dashboard(): void
    {
        $admin = $this->makeAdmin();

        $this->post('/login', ['email' => $admin->email, 'password' => 'password'])
            ->assertRedirect(route('dashboard'));

        $this->assertAuthenticatedAs($admin);

        $this->get('/dashboard')
            ->assertOk()
            ->assertSee($admin->agency->name)
            ->assertSee($admin->name);
    }

    public function test_both_admins_of_the_same_agency_can_use_the_dashboard(): void
    {
        $agency = Agency::factory()->create();
        $first = $this->makeAdmin(['agency_id' => $agency->id]);
        $second = $this->makeAdmin(['agency_id' => $agency->id]);

        foreach ([$first, $second] as $admin) {
            $this->post('/login', ['email' => $admin->email, 'password' => 'password'])
                ->assertRedirect(route('dashboard'));
            $this->get('/dashboard')->assertOk()->assertSee($agency->name);
            $this->post('/logout');
        }
    }

    public function test_wrong_password_returns_a_validation_error(): void
    {
        $admin = $this->makeAdmin();

        $this->from('/login')
            ->post('/login', ['email' => $admin->email, 'password' => 'wrong'])
            ->assertRedirect('/login')
            ->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    public function test_driver_is_refused_with_mobile_app_message(): void
    {
        $driver = User::factory()->driver()->create(['password' => 'password']);

        $response = $this->from('/login')
            ->post('/login', ['email' => $driver->email, 'password' => 'password']);

        $response->assertRedirect('/login')->assertSessionHasErrors('email');
        $this->assertGuest();

        $this->assertStringContainsString(
            'mobile app',
            session('errors')->first('email'),
        );
    }

    public function test_guest_is_redirected_from_dashboard_to_login(): void
    {
        $this->get('/dashboard')->assertRedirect(route('login'));
    }

    public function test_root_redirects_guests_to_login_and_admins_to_dashboard(): void
    {
        $this->get('/')->assertRedirect(route('login'));

        $this->actingAs($this->makeAdmin());
        $this->get('/')->assertRedirect(route('dashboard'));
    }

    public function test_authenticated_admin_visiting_login_is_sent_to_dashboard(): void
    {
        $this->actingAs($this->makeAdmin());

        $this->get('/login')->assertRedirect(route('dashboard'));
    }

    public function test_logout_ends_the_session(): void
    {
        $admin = $this->makeAdmin();
        $this->actingAs($admin);

        $this->post('/logout')->assertRedirect(route('login'));

        $this->assertGuest();
        $this->get('/dashboard')->assertRedirect(route('login'));
    }
}
