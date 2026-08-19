<?php

namespace Tests\Feature;

use App\Models\Agency;
use App\Models\Notification;
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
            'current_password' => 'password',
            'password' => 'new-secret-123',
        ])->assertOk();

        $this->assertTrue(Hash::check('new-secret-123', $this->driver->fresh()->password));
    }

    /* ------------------ the reset is never silent (FR-22 → FR-21) ---------- */

    /**
     * The capability is not the risk; doing it unannounced is. A reset now
     * lands in the affected user's own inbox naming who performed it — the
     * accountability trail every comparable system leaves (2026-08).
     */
    /**
     * The acting administrator confirms who they are first (FR-22, 2026-08).
     * Without it, an unattended logged-in dashboard is enough to take over a
     * driver's account, and the driver only learns of it afterwards.
     */
    public function test_the_reset_is_refused_without_the_admins_own_password(): void
    {
        Sanctum::actingAs($this->admin);

        $this->patchJson("/api/v1/drivers/{$this->driver->id}/password", ['password' => 'new-secret-123'])
            ->assertStatus(422)
            ->assertJsonValidationErrors('current_password');

        $this->assertTrue(Hash::check('password', $this->driver->fresh()->password),
            'The driver password changed even though the administrator was not confirmed.');
    }

    public function test_the_reset_is_refused_when_the_admins_password_is_wrong(): void
    {
        Sanctum::actingAs($this->admin);

        $this->patchJson("/api/v1/drivers/{$this->driver->id}/password", [
            'current_password' => 'not-my-password',
            'password' => 'new-secret-123',
        ])->assertStatus(422)->assertJsonValidationErrors('current_password');

        $this->assertTrue(Hash::check('password', $this->driver->fresh()->password));
    }

    /**
     * The rule must hold on BOTH platforms. The dashboard authenticates on the
     * web session guard and the API on Sanctum, so a rule naming one guard
     * would silently pass on the other.
     */
    public function test_the_confirmation_is_enforced_on_the_dashboard_too(): void
    {
        $this->actingAs($this->admin)
            ->from('/drivers')
            ->patch("/drivers/{$this->driver->id}/password", [
                'password' => 'new-secret-123',
            ])
            ->assertSessionHasErrors('current_password');

        $this->assertTrue(Hash::check('password', $this->driver->fresh()->password));
    }

    /** A wrong password is refused as firmly as a missing one. */
    public function test_the_dashboard_refuses_a_wrong_administrator_password(): void
    {
        $this->actingAs($this->admin)
            ->from('/drivers')
            ->patch("/drivers/{$this->driver->id}/password", [
                'current_password' => 'not-my-password',
                'password' => 'new-secret-123',
            ])
            ->assertSessionHasErrors('current_password');

        $this->assertTrue(Hash::check('password', $this->driver->fresh()->password));
    }

    public function test_a_driver_is_told_who_reset_their_password(): void
    {
        Sanctum::actingAs($this->admin);

        $this->patchJson("/api/v1/drivers/{$this->driver->id}/password", ['current_password' => 'password', 'password' => 'new-secret-123'])
            ->assertOk();

        $notification = Notification::query()
            ->withoutGlobalScopes()
            ->where('user_id', $this->driver->id)
            ->where('type', Notification::TYPE_PASSWORD_RESET)
            ->first();

        $this->assertNotNull($notification, 'The driver was not told their password had been reset.');
        $this->assertStringContainsString($this->admin->name, $notification->message);
    }

    /** Telling the administrator what they just did would be noise. */
    public function test_the_administrator_who_reset_it_is_not_notified(): void
    {
        Sanctum::actingAs($this->admin);

        $this->patchJson("/api/v1/drivers/{$this->driver->id}/password", ['current_password' => 'password', 'password' => 'new-secret-123']);

        $this->assertDatabaseMissing('notifications', [
            'user_id' => $this->admin->id,
            'type' => Notification::TYPE_PASSWORD_RESET,
        ]);
    }

    /** FR-04 is a self-service edit — nobody needs telling what they did themselves. */
    public function test_changing_your_own_password_notifies_nobody(): void
    {
        Sanctum::actingAs($this->driver);

        $this->patchJson('/api/v1/me/profile', [
            'password' => 'my-own-new-123',
            'password_confirmation' => 'my-own-new-123',
        ])->assertOk();

        $this->assertDatabaseMissing('notifications', [
            'type' => Notification::TYPE_PASSWORD_RESET,
        ]);
    }

    public function test_the_dashboard_reset_notifies_too(): void
    {
        $this->actingAs($this->admin)
            ->from('/drivers')
            ->patch(route('drivers.password', $this->driver), [
                'current_password' => 'password',
                'password' => 'new-secret-123',
            ])
            ->assertRedirect('/drivers');

        $this->assertDatabaseHas('notifications', [
            'user_id' => $this->driver->id,
            'type' => Notification::TYPE_PASSWORD_RESET,
        ]);
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
}
