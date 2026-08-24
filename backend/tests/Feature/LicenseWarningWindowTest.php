<?php

namespace Tests\Feature;

use App\Models\Agency;
use App\Models\Notification;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * The licence warning window is actually configurable (FR-08, 2026-08).
 *
 * `agencies.license_expiry_warning_days` was read in four places and written in
 * none: no screen, no endpoint, no command. Every agency sat on the seeded
 * default of 30 forever, while the Chapter 4 data dictionary described the
 * column as "configurable per agency" and the Chapter 4 narrative said these
 * thresholds are "stored as columns rather than constants". The storage claim
 * was true; the capability claim was not.
 *
 * This is deliberately NOT the agency-info editing that design decision 7
 * excludes. That covers the agency's IDENTITY — name, location, contact — which
 * remains display-only. This is an operational threshold FR-08 depends on.
 */
class LicenseWarningWindowTest extends TestCase
{
    use RefreshDatabase;

    private Agency $agency;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->agency = Agency::factory()->create(['code' => 'BFP', 'license_expiry_warning_days' => 30]);
        $this->admin = User::factory()->admin()->create(['agency_id' => $this->agency->id]);
    }

    public function test_the_profile_page_offers_the_setting(): void
    {
        $this->actingAs($this->admin)->get('/profile')
            ->assertOk()
            ->assertSee('Licence Monitoring')
            ->assertSee('license_expiry_warning_days', false);
    }

    public function test_an_admin_changes_the_window(): void
    {
        $this->actingAs($this->admin)
            ->from('/profile')
            ->patch('/agency/license-window', ['license_expiry_warning_days' => 60])
            ->assertRedirect('/profile');

        $this->assertSame(60, $this->agency->fresh()->license_expiry_warning_days);
    }

    /**
     * The point of the setting: it changes what the system reports.
     *
     * Licence status is derived on read rather than stored, so a driver whose
     * licence is 45 days out reads Valid at a 30-day window and Expiring Soon
     * at a 60-day one, with no other change and nothing to recompute.
     */
    public function test_widening_the_window_reclassifies_a_licence_immediately(): void
    {
        $driver = User::factory()->driver()->create([
            'agency_id' => $this->agency->id,
            'license_expiry_date' => now()->addDays(45),
        ]);

        $this->assertSame('Valid', $driver->fresh()->load('agency')->licenseStatus());

        $this->actingAs($this->admin)
            ->patch('/agency/license-window', ['license_expiry_warning_days' => 60]);

        $this->assertSame('Expiring Soon', $driver->fresh()->load('agency')->licenseStatus());
    }

    /** And the Drivers page moves with it, since the cards read the same method. */
    public function test_the_drivers_page_reflects_the_new_window(): void
    {
        User::factory()->driver()->create([
            'agency_id' => $this->agency->id,
            'name' => 'Ramon Villanueva',
            'license_expiry_date' => now()->addDays(45),
        ]);

        $this->actingAs($this->admin)->get('/drivers?license_status=Expiring Soon')
            ->assertOk()
            ->assertDontSee('Ramon Villanueva');

        $this->actingAs($this->admin)
            ->patch('/agency/license-window', ['license_expiry_warning_days' => 60]);

        $this->actingAs($this->admin)->get('/drivers?license_status=Expiring Soon')
            ->assertOk()
            ->assertSee('Ramon Villanueva');
    }

    /** The daily sweep uses the agency's own window too. */
    public function test_the_daily_sweep_honours_the_new_window(): void
    {
        User::factory()->driver()->create([
            'agency_id' => $this->agency->id,
            'license_expiry_date' => now()->addDays(45),
        ]);

        $this->artisan('rvms:license-alerts');
        $this->assertDatabaseCount('notifications', 0);

        $this->actingAs($this->admin)
            ->patch('/agency/license-window', ['license_expiry_warning_days' => 60]);

        $this->artisan('rvms:license-alerts');

        $this->assertDatabaseHas('notifications', [
            'user_id' => $this->admin->id,
            'type' => Notification::TYPE_LICENSE_EXPIRING,
        ]);
    }

    /**
     * A window of zero would send a licence straight from Valid to Expired with
     * no warning at all, which is the one thing FR-08 exists to prevent.
     */
    public function test_a_window_of_zero_is_refused(): void
    {
        $this->actingAs($this->admin)
            ->from('/profile')
            ->patch('/agency/license-window', ['license_expiry_warning_days' => 0])
            ->assertSessionHasErrors('license_expiry_warning_days');

        $this->assertSame(30, $this->agency->fresh()->license_expiry_warning_days);
    }

    public function test_an_absurd_window_is_refused(): void
    {
        $this->actingAs($this->admin)
            ->from('/profile')
            ->patch('/agency/license-window', ['license_expiry_warning_days' => 4000])
            ->assertSessionHasErrors('license_expiry_warning_days');

        $this->assertSame(30, $this->agency->fresh()->license_expiry_warning_days);
    }

    /** FR-02: an admin sets their OWN agency's window and nobody else's. */
    public function test_the_setting_only_touches_the_admins_own_agency(): void
    {
        $other = Agency::factory()->create(['code' => 'CHO', 'license_expiry_warning_days' => 30]);

        $this->actingAs($this->admin)
            ->patch('/agency/license-window', ['license_expiry_warning_days' => 90]);

        $this->assertSame(90, $this->agency->fresh()->license_expiry_warning_days);
        $this->assertSame(30, $other->fresh()->license_expiry_warning_days,
            "One agency's setting changed another agency's threshold.");
    }

    public function test_a_driver_cannot_reach_it(): void
    {
        $driver = User::factory()->driver()->create(['agency_id' => $this->agency->id]);

        // 403 from the role middleware, not a redirect: the dashboard is
        // admin-only and a driver holding a session is refused outright.
        $this->actingAs($driver)
            ->patch('/agency/license-window', ['license_expiry_warning_days' => 90])
            ->assertForbidden();

        $this->assertSame(30, $this->agency->fresh()->license_expiry_warning_days);
    }

    /**
     * Design decision 7 still holds: the agency's identity is not editable, and
     * adding this setting must not have quietly opened that door.
     */
    public function test_the_agency_identity_fields_are_still_read_only(): void
    {
        $html = $this->actingAs($this->admin)->get('/profile')->getContent();

        foreach (['js-agency-name-input', 'js-agency-location', 'js-agency-contact'] as $field) {
            $this->assertMatchesRegularExpression(
                '/<input[^>]*'.$field.'[^>]*\bdisabled\b/',
                $html,
                "The agency {$field} input is no longer disabled (design decision 7)."
            );
        }
    }
}
