<?php

namespace Tests\Feature;

use App\Models\Agency;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RegisterTest extends TestCase
{
    use RefreshDatabase;

    public function test_self_registration_creates_pending_driver(): void
    {
        $agency = Agency::factory()->create();

        $response = $this->postJson('/api/v1/register', [
            'agency_id' => $agency->id,
            'name' => 'Juan Dela Cruz',
            'email' => 'juan@example.com',
            'password' => 'secret-password',
            'password_confirmation' => 'secret-password',
        ]);

        $response->assertCreated()
            ->assertJsonPath('user.role', User::ROLE_DRIVER)
            ->assertJsonPath('user.status', User::STATUS_PENDING)
            ->assertJsonPath('user.agency_id', $agency->id);

        $this->assertDatabaseHas('users', [
            'email' => 'juan@example.com',
            'role' => User::ROLE_DRIVER,
            'status' => User::STATUS_PENDING,
            'agency_id' => $agency->id,
        ]);
    }

    public function test_duplicate_email_returns_422(): void
    {
        $agency = Agency::factory()->create();
        User::factory()->create(['email' => 'taken@example.com']);

        $this->postJson('/api/v1/register', [
            'agency_id' => $agency->id,
            'name' => 'Juan Dela Cruz',
            'email' => 'taken@example.com',
            'password' => 'secret-password',
            'password_confirmation' => 'secret-password',
        ])->assertStatus(422)->assertJsonValidationErrors(['email']);
    }

    public function test_unknown_agency_returns_422(): void
    {
        $this->postJson('/api/v1/register', [
            'agency_id' => 999,
            'name' => 'Juan Dela Cruz',
            'email' => 'juan@example.com',
            'password' => 'secret-password',
            'password_confirmation' => 'secret-password',
        ])->assertStatus(422)->assertJsonValidationErrors(['agency_id']);
    }

    public function test_registered_account_is_always_a_driver(): void
    {
        $agency = Agency::factory()->create();

        $this->postJson('/api/v1/register', [
            'agency_id' => $agency->id,
            'role' => 'admin', // must be ignored — registration is driver-only
            'name' => 'Sneaky User',
            'email' => 'sneaky@example.com',
            'password' => 'secret-password',
            'password_confirmation' => 'secret-password',
        ])->assertCreated()->assertJsonPath('user.role', User::ROLE_DRIVER);

        $this->assertDatabaseHas('users', [
            'email' => 'sneaky@example.com',
            'role' => User::ROLE_DRIVER,
        ]);
    }
}
