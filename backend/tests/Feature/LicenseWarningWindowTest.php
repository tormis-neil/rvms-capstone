<?php

namespace Tests\Feature;

use App\Models\Agency;
use App\Models\Notification;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * The licence warning window (FR-08).
 *
 * `agencies.license_expiry_warning_days` decides how early a licence is flagged
 * Expiring Soon. It is stored per agency and set at deployment; it defaults
 * to 30 days.
 *
 * An on-screen control to change it was built and then REMOVED (2026-08,
 * lead-reported). The reason is worth keeping: nobody needed to change the
 * number, and a setting nobody uses is a thing to explain at a defense for no
 * return. What WAS missing is different — the rule itself was invisible, so an
 * administrator seeing "Expiring Soon" had no way to know what it meant. The
 * Drivers page now states the rule in plain language instead of offering a box.
 *
 * Nothing about the logic changed when the control went: the column still
 * exists, and the Drivers page, the row badges, the daily sweep and the
 * driver's phone all still read it.
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

    /* ------------------------- the rule is stated ------------------------- */

    /**
     * The number is on screen, so "Expiring Soon" is self-explanatory. This is
     * the half of the removed setting that was actually worth keeping.
     */
    public function test_the_drivers_page_states_the_rule(): void
    {
        $this->actingAs($this->admin)->get('/drivers')
            ->assertOk()
            ->assertSee('Expiring Soon')
            ->assertSee('30 days before it expires')
            ->assertSee('the day after its expiry date');
    }

    /** It is read from the column, so a differently-deployed agency reads true. */
    public function test_the_stated_rule_follows_the_agencys_own_value(): void
    {
        $this->agency->update(['license_expiry_warning_days' => 45]);

        $this->actingAs($this->admin)->get('/drivers')
            ->assertOk()
            ->assertSee('45 days before it expires');
    }

    /**
     * The control is gone and must stay gone — it is a statement, not a form.
     */
    public function test_there_is_no_control_to_change_it(): void
    {
        $html = $this->actingAs($this->admin)->get('/drivers')->getContent();

        $this->assertStringNotContainsString('license_expiry_warning_days', $html,
            'An input for the warning window is back on the Drivers page. It was removed '
            .'deliberately: the rule is stated, not offered as a setting.');

        $this->assertFalse(
            \Illuminate\Support\Facades\Route::has('agency.license-window'),
            'The route for editing the warning window is back.'
        );
    }

    public function test_it_is_not_on_the_agency_profile_page_either(): void
    {
        $this->actingAs($this->admin)->get('/profile')
            ->assertOk()
            ->assertDontSee('license_expiry_warning_days', false);
    }

    /* --------------------- the logic still uses it ------------------------ */

    /**
     * The point of the column: it changes what the system reports. Licence
     * status is derived on read rather than stored, so a driver 45 days out
     * reads Valid at a 30-day window and Expiring Soon at a 60-day one, with
     * nothing to recompute.
     */
    public function test_the_window_decides_the_status(): void
    {
        $driver = User::factory()->driver()->create([
            'agency_id' => $this->agency->id,
            'license_expiry_date' => now()->addDays(45),
        ]);

        $this->assertSame('Valid', $driver->fresh()->load('agency')->licenseStatus());

        $this->agency->update(['license_expiry_warning_days' => 60]);

        $this->assertSame('Expiring Soon', $driver->fresh()->load('agency')->licenseStatus());
    }

    /** Expired needs no window — it is simply the day after the expiry date. */
    public function test_expired_does_not_depend_on_the_window(): void
    {
        $driver = User::factory()->driver()->create([
            'agency_id' => $this->agency->id,
            'license_expiry_date' => now()->subDay(),
        ]);

        foreach ([1, 30, 365] as $window) {
            $this->agency->update(['license_expiry_warning_days' => $window]);

            $this->assertSame('Expired', $driver->fresh()->load('agency')->licenseStatus(),
                "A window of {$window} changed what counts as Expired. It must not.");
        }
    }

    /** A licence is valid THROUGH its expiry date, not up to the day before. */
    public function test_a_licence_is_still_valid_on_its_expiry_date(): void
    {
        $driver = User::factory()->driver()->create([
            'agency_id' => $this->agency->id,
            'license_expiry_date' => now()->toDateString(),
        ]);

        $this->assertSame('Expiring Soon', $driver->fresh()->load('agency')->licenseStatus());
    }

    public function test_the_drivers_page_filter_follows_the_window(): void
    {
        User::factory()->driver()->create([
            'agency_id' => $this->agency->id,
            'name' => 'Ramon Villanueva',
            'license_expiry_date' => now()->addDays(45),
        ]);

        $this->actingAs($this->admin)->get('/drivers?license_status=Expiring Soon')
            ->assertOk()
            ->assertDontSee('Ramon Villanueva');

        $this->agency->update(['license_expiry_warning_days' => 60]);

        $this->actingAs($this->admin)->get('/drivers?license_status=Expiring Soon')
            ->assertOk()
            ->assertSee('Ramon Villanueva');
    }

    /** The daily sweep reads the same column. */
    public function test_the_daily_sweep_honours_the_window(): void
    {
        User::factory()->driver()->create([
            'agency_id' => $this->agency->id,
            'license_expiry_date' => now()->addDays(45),
        ]);

        $this->artisan('rvms:license-alerts');
        $this->assertDatabaseCount('notifications', 0);

        $this->agency->update(['license_expiry_warning_days' => 60]);

        $this->artisan('rvms:license-alerts');

        $this->assertDatabaseHas('notifications', [
            'user_id' => $this->admin->id,
            'type' => Notification::TYPE_LICENSE_EXPIRING,
        ]);
    }

    /** FR-02: the window is per agency, so one agency's cannot affect another. */
    public function test_the_window_is_per_agency(): void
    {
        $other = Agency::factory()->create(['code' => 'CHO', 'license_expiry_warning_days' => 30]);
        $otherDriver = User::factory()->driver()->create([
            'agency_id' => $other->id,
            'license_expiry_date' => now()->addDays(45),
        ]);
        $ownDriver = User::factory()->driver()->create([
            'agency_id' => $this->agency->id,
            'license_expiry_date' => now()->addDays(45),
        ]);

        $this->agency->update(['license_expiry_warning_days' => 60]);

        $this->assertSame('Expiring Soon', $ownDriver->fresh()->load('agency')->licenseStatus());
        $this->assertSame('Valid', $otherDriver->fresh()->load('agency')->licenseStatus(),
            "One agency's window changed another agency's licence status.");
    }

    /**
     * Design decision 7 still holds — the agency's identity is not editable,
     * and none of this ever opened that door.
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
