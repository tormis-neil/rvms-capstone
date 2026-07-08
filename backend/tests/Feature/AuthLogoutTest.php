<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthLogoutTest extends TestCase
{
    use RefreshDatabase;

    public function test_logout_revokes_the_token_and_reuse_returns_401(): void
    {
        $user = User::factory()->driver()->create(['password' => 'password']);

        $token = $this->postJson('/api/v1/login', [
            'email' => $user->email,
            'password' => 'password',
        ])->json('token');

        $headers = ['Authorization' => 'Bearer '.$token];

        $this->postJson('/api/v1/logout', [], $headers)
            ->assertOk()
            ->assertJsonPath('message', 'Logged out successfully.');

        $this->assertSame(0, $user->tokens()->count());

        // A revoked token must be rejected on reuse. Reset the guard cache so
        // the next in-test request re-authenticates from the (deleted) token.
        $this->app['auth']->forgetGuards();
        $this->getJson('/api/v1/me', $headers)->assertStatus(401);
    }
}
