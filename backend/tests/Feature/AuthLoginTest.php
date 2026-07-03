<?php

namespace Tests\Feature;

use App\Models\Agency;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthLoginTest extends TestCase
{
    use RefreshDatabase;

    private function makeAdmin(array $attributes = []): User
    {
        return User::factory()->admin()->create($attributes + [
            'email' => 'admin@example.com',
            'password' => 'password',
        ]);
    }

    public function test_valid_credentials_return_token_role_and_agency(): void
    {
        $admin = $this->makeAdmin();

        $response = $this->postJson('/api/v1/login', [
            'email' => 'admin@example.com',
            'password' => 'password',
        ]);

        $response->assertOk()
            ->assertJsonStructure([
                'token',
                'user' => ['id', 'role', 'name', 'email', 'agency' => ['id', 'code', 'name']],
            ])
            ->assertJsonPath('user.role', User::ROLE_ADMIN)
            ->assertJsonPath('user.agency.id', $admin->agency_id);

        $this->assertNotEmpty($response->json('token'));
    }

    public function test_wrong_password_returns_422(): void
    {
        $this->makeAdmin();

        $this->postJson('/api/v1/login', [
            'email' => 'admin@example.com',
            'password' => 'wrong-password',
        ])->assertStatus(422)->assertJsonValidationErrors(['email']);
    }

    public function test_missing_email_and_password_return_descriptive_422(): void
    {
        $response = $this->postJson('/api/v1/login', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email', 'password']);

        $this->assertStringContainsString('email address is required', $response->json('errors.email.0'));
        $this->assertStringContainsString('password is required', $response->json('errors.password.0'));
    }

    public function test_pending_driver_cannot_log_in(): void
    {
        User::factory()->driver()->pending()->create([
            'email' => 'pending.driver@example.com',
            'password' => 'password',
        ]);

        $this->postJson('/api/v1/login', [
            'email' => 'pending.driver@example.com',
            'password' => 'password',
        ])->assertStatus(403)
            ->assertJsonPath('message', 'Your account is pending approval by your agency administrator.');
    }

    public function test_rejected_driver_cannot_log_in(): void
    {
        User::factory()->driver()->rejected()->create([
            'email' => 'rejected.driver@example.com',
            'password' => 'password',
        ]);

        $this->postJson('/api/v1/login', [
            'email' => 'rejected.driver@example.com',
            'password' => 'password',
        ])->assertStatus(403);
    }

    public function test_active_driver_can_log_in(): void
    {
        User::factory()->driver()->create([
            'email' => 'driver@example.com',
            'password' => 'password',
        ]);

        $this->postJson('/api/v1/login', [
            'email' => 'driver@example.com',
            'password' => 'password',
        ])->assertOk()->assertJsonPath('user.role', User::ROLE_DRIVER);
    }
}
