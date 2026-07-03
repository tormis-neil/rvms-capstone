<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ProfileTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_updates_own_name_email_and_password(): void
    {
        $user = User::factory()->driver()->create();
        Sanctum::actingAs($user);

        $this->patchJson('/api/v1/me/profile', [
            'name' => 'Updated Name',
            'email' => 'updated@example.com',
            'password' => 'new-password-123',
            'password_confirmation' => 'new-password-123',
        ])->assertOk()
            ->assertJsonPath('user.name', 'Updated Name')
            ->assertJsonPath('user.email', 'updated@example.com');

        $user->refresh();
        $this->assertSame('Updated Name', $user->name);
        $this->assertSame('updated@example.com', $user->email);
        $this->assertTrue(Hash::check('new-password-123', $user->password));
    }

    public function test_partial_update_only_touches_given_fields(): void
    {
        $user = User::factory()->admin()->create(['name' => 'Original', 'email' => 'orig@example.com']);
        Sanctum::actingAs($user);

        $this->patchJson('/api/v1/me/profile', ['name' => 'Renamed Only'])
            ->assertOk();

        $user->refresh();
        $this->assertSame('Renamed Only', $user->name);
        $this->assertSame('orig@example.com', $user->email);
    }

    public function test_profile_update_cannot_modify_another_user(): void
    {
        $victim = User::factory()->driver()->create(['name' => 'Victim', 'email' => 'victim@example.com']);
        $attacker = User::factory()->driver()->create();
        Sanctum::actingAs($attacker);

        // Injected identifiers must be ignored — the endpoint only ever edits the caller.
        $this->patchJson('/api/v1/me/profile', [
            'id' => $victim->id,
            'user_id' => $victim->id,
            'name' => 'Hacked',
        ])->assertOk();

        $this->assertSame('Victim', $victim->refresh()->name);
        $this->assertSame('Hacked', $attacker->refresh()->name);
    }

    public function test_cannot_take_another_users_email(): void
    {
        User::factory()->create(['email' => 'taken@example.com']);
        $user = User::factory()->driver()->create();
        Sanctum::actingAs($user);

        $this->patchJson('/api/v1/me/profile', ['email' => 'taken@example.com'])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
    }

    public function test_guest_cannot_update_profile(): void
    {
        $this->patchJson('/api/v1/me/profile', ['name' => 'Nobody'])
            ->assertStatus(401);
    }
}
