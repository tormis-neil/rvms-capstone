<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthMeTest extends TestCase
{
    use RefreshDatabase;

    public function test_me_returns_authenticated_user_with_valid_token(): void
    {
        $user = User::factory()->driver()->create(['password' => 'password']);

        $token = $this->postJson('/api/v1/login', [
            'email' => $user->email,
            'password' => 'password',
        ])->json('token');

        $this->getJson('/api/v1/me', ['Authorization' => 'Bearer '.$token])
            ->assertOk()
            ->assertJsonPath('user.id', $user->id)
            ->assertJsonPath('user.email', $user->email)
            ->assertJsonPath('user.agency.id', $user->agency_id);
    }

    public function test_me_returns_401_without_token(): void
    {
        $this->getJson('/api/v1/me')->assertStatus(401);
    }

    public function test_me_returns_401_with_invalid_token(): void
    {
        $this->getJson('/api/v1/me', ['Authorization' => 'Bearer not-a-real-token'])
            ->assertStatus(401);
    }
}
