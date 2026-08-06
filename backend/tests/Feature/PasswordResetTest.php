<?php

namespace Tests\Feature;

use App\Models\Agency;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/**
 * Recovering a forgotten password without email (FR-22, 2026-08).
 *
 * The system sends no mail, deliberately: SMTP credentials are one more thing
 * to configure at turnover and one more thing to fail silently, which is the
 * same objection that took the queue worker out of the deployment. So recovery
 * is three layers that need no infrastructure at all —
 *
 *   1. an administrator resets a driver (the routine case),
 *   2. administrators reset each other (the case that prevents lockout),
 *   3. `rvms:reset-password` at the server (the sole-administrator fallback).
 *
 * Together they cover every account in the system.
 */
class PasswordResetTest extends TestCase
{
    use RefreshDatabase;

    private Agency $agency;

    private User $admin;

    private User $colleague;

    private User $driver;

    protected function setUp(): void
    {
        parent::setUp();
        $this->agency = Agency::factory()->create(['code' => 'BFP']);
        $this->admin = User::factory()->admin()->create([
            'agency_id' => $this->agency->id,
            'password' => 'password',
        ]);
        $this->colleague = User::factory()->admin()->create(['agency_id' => $this->agency->id]);
        $this->driver = User::factory()->driver()->create(['agency_id' => $this->agency->id]);
    }

    /* --------------------- 1. admin resets a driver ----------------------- */

    public function test_an_admin_sets_a_new_password_for_their_driver(): void
    {
        Sanctum::actingAs($this->admin);

        $this->patchJson("/api/v1/drivers/{$this->driver->id}/password", [
            'password' => 'new-secret-123',
        ])->assertOk();

        $this->assertTrue(Hash::check('new-secret-123', $this->driver->fresh()->password));
    }

    /** The driver can then actually sign in with it — the point of the feature. */
    public function test_the_driver_can_sign_in_with_the_new_password(): void
    {
        Sanctum::actingAs($this->admin);
        $this->patchJson("/api/v1/drivers/{$this->driver->id}/password", ['password' => 'new-secret-123']);

        $this->postJson('/api/v1/login', [
            'email' => $this->driver->email,
            'password' => 'new-secret-123',
        ])->assertOk()->assertJsonStructure(['token']);
    }

    /** A reset is often prompted by a handset nobody controls any more. */
    public function test_resetting_revokes_the_drivers_existing_tokens(): void
    {
        $this->driver->createToken('old-phone');
        $this->assertSame(1, $this->driver->tokens()->count());

        Sanctum::actingAs($this->admin);
        $this->patchJson("/api/v1/drivers/{$this->driver->id}/password", ['password' => 'new-secret-123'])->assertOk();

        $this->assertSame(0, $this->driver->fresh()->tokens()->count());
    }

    public function test_a_short_password_is_refused(): void
    {
        Sanctum::actingAs($this->admin);

        $this->patchJson("/api/v1/drivers/{$this->driver->id}/password", ['password' => 'short'])
            ->assertStatus(422)
            ->assertJsonValidationErrors('password');
    }

    public function test_another_agencys_driver_cannot_be_reset(): void
    {
        $other = Agency::factory()->create(['code' => 'PNP']);
        $foreign = User::factory()->driver()->create(['agency_id' => $other->id, 'password' => 'password']);

        Sanctum::actingAs($this->admin);
        $this->patchJson("/api/v1/drivers/{$foreign->id}/password", ['password' => 'new-secret-123'])
            ->assertNotFound();

        $this->assertTrue(Hash::check('password', $foreign->fresh()->password));
    }

    public function test_a_driver_cannot_reset_anyones_password(): void
    {
        Sanctum::actingAs($this->driver);

        $this->patchJson("/api/v1/drivers/{$this->driver->id}/password", ['password' => 'new-secret-123'])
            ->assertForbidden();
    }

    /* ------------------ 2. admins reset each other ------------------------ */

    public function test_an_admin_lists_only_their_own_agencys_colleagues(): void
    {
        $other = Agency::factory()->create(['code' => 'PNP']);
        User::factory()->admin()->create(['agency_id' => $other->id]);

        Sanctum::actingAs($this->admin);
        $response = $this->getJson('/api/v1/admins')->assertOk();

        // The colleague, and not the caller or the other agency's admin.
        $response->assertJsonCount(1, 'data');
        $response->assertJsonPath('data.0.id', $this->colleague->id);
    }

    public function test_an_admin_resets_a_locked_out_colleague(): void
    {
        Sanctum::actingAs($this->admin);

        $this->patchJson("/api/v1/admins/{$this->colleague->id}/password", [
            'current_password' => 'password',
            'password' => 'colleague-new-123',
        ])->assertOk();

        $this->assertTrue(Hash::check('colleague-new-123', $this->colleague->fresh()->password));
    }

    /**
     * The guard that separates this from a driver reset: an unattended session
     * must not be enough to take over a peer administrator's account.
     */
    public function test_resetting_a_colleague_requires_your_own_password(): void
    {
        Sanctum::actingAs($this->admin);

        $this->patchJson("/api/v1/admins/{$this->colleague->id}/password", [
            'current_password' => 'wrong-password',
            'password' => 'colleague-new-123',
        ])->assertStatus(422)->assertJsonValidationErrors('current_password');

        $this->assertFalse(Hash::check('colleague-new-123', $this->colleague->fresh()->password));
    }

    public function test_the_confirmation_is_required_not_optional(): void
    {
        Sanctum::actingAs($this->admin);

        $this->patchJson("/api/v1/admins/{$this->colleague->id}/password", [
            'password' => 'colleague-new-123',
        ])->assertStatus(422)->assertJsonValidationErrors('current_password');
    }

    /** Your own password is FR-04's job, on the profile page. */
    public function test_an_admin_cannot_reset_themselves_through_this_route(): void
    {
        Sanctum::actingAs($this->admin);

        $this->patchJson("/api/v1/admins/{$this->admin->id}/password", [
            'current_password' => 'password',
            'password' => 'my-new-password',
        ])->assertNotFound();
    }

    public function test_another_agencys_admin_cannot_be_reset(): void
    {
        $other = Agency::factory()->create(['code' => 'PNP']);
        $foreign = User::factory()->admin()->create(['agency_id' => $other->id, 'password' => 'password']);

        Sanctum::actingAs($this->admin);
        $this->patchJson("/api/v1/admins/{$foreign->id}/password", [
            'current_password' => 'password',
            'password' => 'foreign-new-123',
        ])->assertNotFound();

        $this->assertTrue(Hash::check('password', $foreign->fresh()->password));
    }

    /** A driver must never reach the administrator endpoints. */
    public function test_a_driver_cannot_list_or_reset_admins(): void
    {
        Sanctum::actingAs($this->driver);

        $this->getJson('/api/v1/admins')->assertForbidden();
        $this->patchJson("/api/v1/admins/{$this->admin->id}/password", [
            'current_password' => 'password',
            'password' => 'x-new-password',
        ])->assertForbidden();
    }

    /* -------------------- 3. the console fallback ------------------------- */

    public function test_the_command_resets_a_sole_administrator(): void
    {
        $this->artisan('rvms:reset-password', ['email' => $this->admin->email])
            ->expectsQuestion('New password (min 8 characters)', 'recovered-123')
            ->expectsOutputToContain('Password updated')
            ->assertExitCode(0);

        $this->assertTrue(Hash::check('recovered-123', $this->admin->fresh()->password));
    }

    public function test_the_command_refuses_a_short_password(): void
    {
        $this->artisan('rvms:reset-password', ['email' => $this->admin->email])
            ->expectsQuestion('New password (min 8 characters)', 'short')
            ->assertExitCode(1);

        $this->assertTrue(Hash::check('password', $this->admin->fresh()->password));
    }

    /** An unknown email lists the administrators, so a typo is self-correcting. */
    public function test_an_unknown_email_lists_the_administrators(): void
    {
        $this->artisan('rvms:reset-password', ['email' => 'nobody@rvms.local'])
            ->expectsOutputToContain('No account found')
            ->assertExitCode(1);
    }

    public function test_the_command_refuses_a_deleted_account(): void
    {
        $this->driver->delete();

        $this->artisan('rvms:reset-password', ['email' => $this->driver->email])
            ->expectsOutputToContain('deleted account')
            ->assertExitCode(1);
    }
}
