<?php

namespace Tests\Feature;

use App\Models\Agency;
use App\Models\Dispatch;
use App\Models\User;
use App\Models\Vehicle;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Licence state is visible at the moment of dispatch (FR-08 → FR-15, 2026-08).
 *
 * The system detected expiry and did nothing with it. The dispatch form checked
 * that the driver existed, belonged to the agency and had no other open
 * dispatch — and never looked at the licence, so a driver whose licence lapsed
 * a year ago could be sent out today without a word, by the very system built
 * to watch licences.
 *
 * It WARNS rather than BLOCKS, and that is the deliberate part. These are
 * emergency vehicles: refusing a dispatch during a fire or a medical call would
 * be worse than the paperwork problem it solves, and a system that does it gets
 * worked around within a week. FR-08 also says the system alerts — it does not
 * say it withholds a vehicle. So the administrator is told plainly, confirms an
 * expired licence explicitly, and the decision stays theirs.
 */
class DispatchLicenseWarningTest extends TestCase
{
    use RefreshDatabase;

    private Agency $agency;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->agency = Agency::factory()->create(['code' => 'BFP', 'license_expiry_warning_days' => 30]);
        $this->admin = User::factory()->admin()->create(['agency_id' => $this->agency->id]);

        Vehicle::factory()->create([
            'agency_id' => $this->agency->id,
            'plate_number' => 'BFP-0001',
            'status' => Vehicle::STATUS_OPERATIONAL,
        ]);
    }

    private function driver(string $name, ?string $expiry): User
    {
        return User::factory()->driver()->create([
            'agency_id' => $this->agency->id,
            'name' => $name,
            'license_expiry_date' => $expiry,
        ]);
    }

    private function dispatchPage(): string
    {
        return $this->actingAs($this->admin)->get('/dispatch')->assertOk()->getContent();
    }

    public function test_an_expired_licence_is_named_in_the_driver_list(): void
    {
        $this->driver('Joy Baracuda', now()->subMonths(2)->toDateString());

        $html = $this->dispatchPage();

        $this->assertStringContainsString('data-license-status="Expired"', $html);
        $this->assertStringContainsString('licence EXPIRED', $html);
    }

    public function test_an_expiring_licence_is_named_with_its_date(): void
    {
        $driver = $this->driver('Ramon Villanueva', now()->addDays(10)->toDateString());

        $html = $this->dispatchPage();

        $this->assertStringContainsString('data-license-status="Expiring Soon"', $html);
        $this->assertStringContainsString($driver->license_expiry_date->format('M j, Y'), $html);
    }

    /** A valid licence adds no noise to the form. */
    public function test_a_valid_licence_is_not_flagged(): void
    {
        $this->driver('Marvin Salazar', now()->addYears(2)->toDateString());

        $html = $this->dispatchPage();

        $this->assertStringContainsString('data-license-status="Valid"', $html);
        $this->assertStringNotContainsString('licence EXPIRED', $html);
    }

    /** A driver with no licence recorded is neither flagged nor crashed on. */
    public function test_a_driver_without_a_licence_date_is_handled(): void
    {
        $this->driver('No Licence Recorded', null);

        $html = $this->dispatchPage();

        $this->assertStringContainsString('data-license-status=""', $html);
        $this->assertStringNotContainsString('licence EXPIRED', $html);
    }

    /**
     * The flag follows the agency's own warning window, which is set per agency
     * at deployment rather than from a screen (the on-screen control was
     * removed, 2026-08 — see LicenseWarningWindowTest).
     */
    public function test_the_flag_follows_the_agencys_configured_window(): void
    {
        $this->driver('Ramon Villanueva', now()->addDays(45)->toDateString());

        $this->assertStringContainsString('data-license-status="Valid"', $this->dispatchPage());

        $this->agency->update(['license_expiry_warning_days' => 60]);

        $this->assertStringContainsString('data-license-status="Expiring Soon"', $this->dispatchPage());
    }

    /**
     * The rule this test exists to protect: an expired licence must NOT stop
     * the dispatch. An emergency cannot wait for a renewal, and the requirement
     * says alert, not withhold.
     */
    public function test_an_expired_licence_does_not_block_the_dispatch(): void
    {
        $driver = $this->driver('Joy Baracuda', now()->subMonths(2)->toDateString());
        $vehicle = Vehicle::query()->firstOrFail();

        $this->actingAs($this->admin)
            ->from('/dispatch')
            ->post('/dispatch', [
                'vehicle_id' => $vehicle->id,
                'driver_id' => $driver->id,
                'mission_type' => 'Fire Response',
                'location' => 'Brgy. Obrero',
                'time_out' => now()->toDateTimeString(),
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('dispatches', [
            'driver_id' => $driver->id,
            'vehicle_id' => $vehicle->id,
        ]);

        $this->assertSame(Vehicle::STATUS_DISPATCHED, $vehicle->fresh()->status);
    }

    /** The form carries the confirmation the admin has to answer. */
    public function test_the_form_confirms_before_dispatching_on_an_expired_licence(): void
    {
        $this->driver('Joy Baracuda', now()->subMonths(2)->toDateString());

        $html = $this->dispatchPage();

        $this->assertStringContainsString('js-nd-licence', $html);
        $this->assertStringContainsString("licence expired on", $html);
        $this->assertStringContainsString('Continue with this dispatch?', $html);
    }

    /**
     * Reading the licence for every driver must not cost a query per driver —
     * licenseStatus() reads the agency's warning window, so the agency has to
     * be eager-loaded (R10.4 N+1).
     */
    public function test_the_driver_list_does_not_query_per_driver(): void
    {
        for ($i = 0; $i < 8; $i++) {
            $this->driver("Driver {$i}", now()->addDays(10)->toDateString());
        }

        \DB::enableQueryLog();
        $this->actingAs($this->admin)->get('/dispatch')->assertOk();
        $count = count(\DB::getQueryLog());
        \DB::disableQueryLog();

        $this->assertLessThan(30, $count,
            "The dispatch page ran {$count} queries — the driver licence lookup is probably N+1.");
    }
}
