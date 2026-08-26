<?php

namespace Tests\Feature;

use App\Models\Agency;
use App\Models\Dispatch;
use App\Models\User;
use App\Models\Vehicle;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * The dispatch form never proposes a vehicle/driver pairing on its own
 * (2026-08, lead-reported).
 *
 * Each select helps fill the other, which is what an administrator wants. But
 * the old code could not tell a value IT had filled from one the admin had
 * chosen, and it only ever wrote to the vehicle select in one of its three
 * branches. So choosing a driver with no assigned vehicle left the PREVIOUS
 * driver's vehicle sitting in the field, and submitting recorded a pairing
 * nobody made. Neither select had a placeholder either, so the form opened with
 * a vehicle already chosen and `required` could never fire.
 *
 * What is NOT a bug, and must not be "fixed": dispatching a driver who is not
 * the vehicle's primary driver. Chapter 1 records that rotating, shift-based
 * arrangements — as CHO practises — are attributed by the DISPATCH RECORD
 * rather than the vehicle assignment. Blocking it would break a workflow taken
 * from the interviews. The form therefore states the pairing plainly instead of
 * refusing it.
 *
 * The interaction itself is client-side and was verified in headless Chromium
 * across six flows before these assertions were written; what is checked here
 * is that the markup and the logic those flows depend on are still present.
 */
class DispatchPairingTest extends TestCase
{
    use RefreshDatabase;

    private Agency $agency;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->agency = Agency::factory()->create(['code' => 'CDRRMO']);
        $this->admin = User::factory()->admin()->create(['agency_id' => $this->agency->id]);
    }

    private function page(): string
    {
        return $this->actingAs($this->admin)->get('/dispatch')->assertOk()->getContent();
    }

    /* ------------------------------- A ----------------------------------- */

    /**
     * Both selects open on a placeholder. Without one the browser selects the
     * first row, so the form proposed a pairing before the admin touched it —
     * and `required` was decorative, because a value was always present.
     */
    public function test_both_selects_open_with_nothing_chosen(): void
    {
        $driver = User::factory()->driver()->create(['agency_id' => $this->agency->id]);
        Vehicle::factory()->create([
            'agency_id' => $this->agency->id,
            'assigned_driver_id' => $driver->id,
            'status' => Vehicle::STATUS_OPERATIONAL,
        ]);

        $html = $this->page();

        $this->assertStringContainsString('Select a vehicle', $html);
        $this->assertStringContainsString('Select a driver', $html);

        // Each placeholder must be the selected option and unselectable, or the
        // browser will simply fall back to the first real row.
        $this->assertSame(
            2,
            preg_match_all('/<option value="" selected disabled>Select a (vehicle|driver)/', $html),
            'A placeholder option is missing or is not the selected, disabled one.'
        );
    }

    /* ------------------------------- B ----------------------------------- */

    /** The provenance flags are what let an auto-filled value be cleared. */
    public function test_the_form_tracks_which_values_it_filled_in(): void
    {
        $html = $this->page();

        foreach (['vehicleAutoFilled', 'driverAutoFilled'] as $flag) {
            $this->assertStringContainsString($flag, $html,
                "The {$flag} provenance flag is gone — an auto-filled value can no longer be told "
                .'apart from one the admin chose, which is the bug this fixes.');
        }
    }

    /**
     * A change the admin makes clears that field's flag, so their choice is
     * never afterwards overwritten or cleared. This is what keeps the reverse
     * flow — vehicle first, then driver — working.
     */
    public function test_a_manual_change_clears_the_auto_filled_flag(): void
    {
        $html = $this->page();

        $this->assertMatchesRegularExpression(
            "/vehicleSelect\.addEventListener\('change', \(\) => \{ vehicleAutoFilled = false;/",
            $html
        );
        $this->assertMatchesRegularExpression(
            "/driverSelect\.addEventListener\('change', \(\) => \{ driverAutoFilled = false;/",
            $html
        );
    }

    /** Opening the modal resets both selects, so a reopened form starts clean. */
    public function test_reopening_the_modal_starts_from_nothing(): void
    {
        $this->assertMatchesRegularExpression(
            "/show\.bs\.modal.*?vehicleSelect\.value = '';.*?driverSelect\.value = '';/s",
            $this->page()
        );
    }

    /* ------------------------------- C ----------------------------------- */

    public function test_the_form_describes_the_pairing_rather_than_refusing_it(): void
    {
        $html = $this->page();

        $this->assertStringContainsString('is not the primary driver of', $html);
        $this->assertStringContainsString(
            'This is allowed — the dispatch record is what attributes the trip',
            $html,
            'The note that a non-primary pairing is ALLOWED is gone. Without it a deliberate '
            .'CHO-style rotation reads as a mistake.'
        );
        $this->assertStringContainsString('has no vehicle assigned. Choose any operational vehicle above.', $html);
    }

    /* ------------------- the rule that must NOT change -------------------- */

    /**
     * A driver who is not the vehicle's primary driver can still be dispatched.
     * Chapter 1 depends on this: it is how CHO's rotating arrangement is
     * recorded, and the fix above must not have quietly turned the warning into
     * a refusal.
     */
    public function test_a_driver_with_no_assigned_vehicle_can_still_be_dispatched(): void
    {
        $joseph = User::factory()->driver()->create([
            'agency_id' => $this->agency->id,
            'name' => 'Joseph Cabrera',
        ]);

        $vehicle = Vehicle::factory()->create([
            'agency_id' => $this->agency->id,
            'plate_number' => 'CDR-3301',
            'assigned_driver_id' => null,
            'status' => Vehicle::STATUS_OPERATIONAL,
        ]);

        $this->actingAs($this->admin)
            ->from('/dispatch')
            ->post('/dispatch', [
                'vehicle_id' => $vehicle->id,
                'driver_id' => $joseph->id,
                'mission_type' => 'Rescue Operation',
                'location' => 'Brgy. Obrero',
                'time_out' => now()->toDateTimeString(),
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('dispatches', [
            'vehicle_id' => $vehicle->id,
            'driver_id' => $joseph->id,
        ]);
    }

    /** And a driver assigned to a DIFFERENT vehicle may drive this one too. */
    public function test_a_driver_assigned_elsewhere_can_drive_another_vehicle(): void
    {
        $ana = User::factory()->driver()->create(['agency_id' => $this->agency->id]);

        Vehicle::factory()->create([
            'agency_id' => $this->agency->id,
            'plate_number' => 'ANA-0001',
            'assigned_driver_id' => $ana->id,
            'status' => Vehicle::STATUS_OPERATIONAL,
        ]);

        $other = Vehicle::factory()->create([
            'agency_id' => $this->agency->id,
            'plate_number' => 'CDR-9999',
            'assigned_driver_id' => null,
            'status' => Vehicle::STATUS_OPERATIONAL,
        ]);

        $this->actingAs($this->admin)
            ->from('/dispatch')
            ->post('/dispatch', [
                'vehicle_id' => $other->id,
                'driver_id' => $ana->id,
                'mission_type' => 'Medical Response',
                'location' => 'Brgy. Carmen',
                'time_out' => now()->toDateTimeString(),
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('dispatches', [
            'vehicle_id' => $other->id,
            'driver_id' => $ana->id,
        ]);
    }

    /** The existing guards are untouched: still one open dispatch per vehicle. */
    public function test_the_duplicate_dispatch_guard_still_holds(): void
    {
        $driver = User::factory()->driver()->create(['agency_id' => $this->agency->id]);
        $second = User::factory()->driver()->create(['agency_id' => $this->agency->id]);

        $vehicle = Vehicle::factory()->create([
            'agency_id' => $this->agency->id,
            'status' => Vehicle::STATUS_OPERATIONAL,
        ]);

        Dispatch::factory()->create([
            'agency_id' => $this->agency->id,
            'vehicle_id' => $vehicle->id,
            'driver_id' => $driver->id,
            'time_in' => null,
        ]);

        $this->actingAs($this->admin)
            ->from('/dispatch')
            ->post('/dispatch', [
                'vehicle_id' => $vehicle->id,
                'driver_id' => $second->id,
                'mission_type' => 'Patrol',
                'location' => 'Brgy. Obrero',
                'time_out' => now()->toDateTimeString(),
            ])
            ->assertSessionHasErrors('vehicle_id');
    }
}
