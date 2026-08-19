<?php

namespace Tests\Feature;

use App\Models\Agency;
use App\Models\Dispatch;
use App\Models\User;
use App\Models\Vehicle;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * R6 — the Dispatch Logging dashboard page (Blade twin of /api/v1/dispatches).
 */
class WebDispatchPageTest extends TestCase
{
    use RefreshDatabase;

    private Agency $agency;

    private User $admin;

    private User $driver;

    protected function setUp(): void
    {
        parent::setUp();
        $this->agency = Agency::factory()->create(['code' => 'BFP']);
        $this->admin = User::factory()->admin()->create(['agency_id' => $this->agency->id]);
        $this->driver = User::factory()->driver()->create(['agency_id' => $this->agency->id]);
    }

    public function test_page_shows_active_count_and_own_agency_dispatches(): void
    {
        $vehicle = Vehicle::factory()->create(['agency_id' => $this->agency->id]);
        Dispatch::factory()->create([
            'agency_id' => $this->agency->id, 'vehicle_id' => $vehicle->id, 'driver_id' => $this->driver->id,
            'location' => 'Brgy. Obrero',
        ]);

        $other = Agency::factory()->create(['code' => 'PNP']);
        Dispatch::factory()->create([
            'agency_id' => $other->id,
            'vehicle_id' => Vehicle::factory()->create(['agency_id' => $other->id])->id,
            'driver_id' => User::factory()->driver()->create(['agency_id' => $other->id])->id,
            'location' => 'Secret PNP location',
        ]);

        $this->actingAs($this->admin)
            ->get('/dispatch')
            ->assertOk()
            ->assertSee('Dispatch Logging')
            ->assertSee('Brgy. Obrero')
            ->assertSee('1</strong> vehicle', false)
            ->assertDontSee('Secret PNP location');
    }

    public function test_open_dispatch_sets_the_vehicle_dispatched(): void
    {
        $vehicle = Vehicle::factory()->create(['agency_id' => $this->agency->id, 'status' => Vehicle::STATUS_OPERATIONAL]);

        $this->actingAs($this->admin)
            ->from('/dispatch')
            ->post('/dispatch', [
                'vehicle_id' => $vehicle->id,
                'driver_id' => $this->driver->id,
                'mission_type' => 'Patrol',
                'location' => 'Downtown',
                'time_out' => now()->toDateTimeString(),
            ])
            ->assertRedirect(route('dispatch'));

        $this->assertSame(Vehicle::STATUS_DISPATCHED, $vehicle->fresh()->status);
    }

    public function test_close_dispatch_writes_return_status_and_bumps_mileage(): void
    {
        $vehicle = Vehicle::factory()->create(['agency_id' => $this->agency->id, 'current_mileage' => 45000]);
        $dispatch = Dispatch::factory()->create([
            'agency_id' => $this->agency->id, 'vehicle_id' => $vehicle->id, 'driver_id' => $this->driver->id,
        ]);

        $this->actingAs($this->admin)
            ->from('/dispatch')
            ->patch("/dispatch/{$dispatch->id}/close", [
                'time_in' => now()->toDateTimeString(),
                'return_status' => Vehicle::STATUS_OPERATIONAL,
                'odometer_in' => 45300,
            ])
            ->assertRedirect(route('dispatch'));

        $this->assertSame(Vehicle::STATUS_OPERATIONAL, $vehicle->fresh()->status);
        $this->assertSame(45300, $vehicle->fresh()->current_mileage);
    }

    public function test_others_mission_without_detail_is_rejected(): void
    {
        $vehicle = Vehicle::factory()->create(['agency_id' => $this->agency->id]);

        $this->actingAs($this->admin)
            ->from('/dispatch')
            ->post('/dispatch', [
                'vehicle_id' => $vehicle->id,
                'driver_id' => $this->driver->id,
                'mission_type' => 'Others',
                'location' => 'Downtown',
                'time_out' => now()->toDateTimeString(),
            ])
            ->assertSessionHasErrors('mission_other');
    }

    /**
     * The New Dispatch selects must offer exactly the dispatchable vehicles and
     * the agency's own active drivers, and each vehicle option must carry its
     * primary driver so the Driver select can preselect it (FR-05 → FR-15).
     */
    public function test_new_dispatch_selects_offer_the_right_vehicles_and_drivers(): void
    {
        $assignable = Vehicle::factory()->create([
            'agency_id' => $this->agency->id,
            'plate_number' => 'BFP-0001',
            'status' => Vehicle::STATUS_OPERATIONAL,
            'assigned_driver_id' => $this->driver->id,
        ]);
        $unassigned = Vehicle::factory()->create([
            'agency_id' => $this->agency->id,
            'plate_number' => 'BFP-0002',
            'status' => Vehicle::STATUS_OPERATIONAL,
            'assigned_driver_id' => null,
        ]);

        // Not dispatchable: already out, off the road, or in maintenance.
        foreach ([Vehicle::STATUS_DISPATCHED, Vehicle::STATUS_NOT_OPERATIONAL, Vehicle::STATUS_UNDER_PM] as $i => $status) {
            Vehicle::factory()->create([
                'agency_id' => $this->agency->id,
                'plate_number' => 'BFP-90'.$i,
                'status' => $status,
            ]);
        }

        // Must never be offered: another agency's vehicle, and a pending driver.
        $other = Agency::factory()->create(['code' => 'PNP']);
        Vehicle::factory()->create([
            'agency_id' => $other->id, 'plate_number' => 'PNP-7777', 'status' => Vehicle::STATUS_OPERATIONAL,
        ]);
        $pending = User::factory()->driver()->create([
            'agency_id' => $this->agency->id, 'name' => 'Pending Applicant', 'status' => User::STATUS_PENDING,
        ]);

        $html = $this->actingAs($this->admin)->get('/dispatch')->assertOk()->getContent();

        preg_match('#<select[^>]*js-nd-vehicle.*?</select>#s', $html, $vehicleSelect);
        $this->assertNotEmpty($vehicleSelect, 'The New Dispatch vehicle select is missing.');

        $this->assertStringContainsString('BFP-0001', $vehicleSelect[0]);
        $this->assertStringContainsString('BFP-0002', $vehicleSelect[0]);
        $this->assertStringNotContainsString('BFP-900', $vehicleSelect[0]);
        $this->assertStringNotContainsString('BFP-901', $vehicleSelect[0]);
        $this->assertStringNotContainsString('BFP-902', $vehicleSelect[0]);
        $this->assertStringNotContainsString('PNP-7777', $vehicleSelect[0]);

        // Each option carries its primary driver (empty when there is none).
        $this->assertStringContainsString(
            'value="'.$assignable->id.'" data-driver-id="'.$this->driver->id.'"',
            $vehicleSelect[0]
        );
        $this->assertStringContainsString(
            'value="'.$unassigned->id.'" data-driver-id=""',
            $vehicleSelect[0]
        );

        preg_match('#<select[^>]*js-nd-driver.*?</select>#s', $html, $driverSelect);
        $this->assertNotEmpty($driverSelect, 'The New Dispatch driver select is missing.');
        $this->assertStringContainsString($this->driver->name, $driverSelect[0]);
        $this->assertStringNotContainsString($pending->name, $driverSelect[0]);
    }

    /**
     * The Dispatch Details badge was hardcoded to "Completed", so opening the
     * details of a mission still out in the field reported it as finished —
     * contradicting the row behind it and the active-count banner above it
     * (2026-08, lead-reported).
     *
     * HTTP tests cannot run the page's JavaScript, so this asserts the two
     * things the fix depends on: the row publishes whether the dispatch is
     * active, and the badge is no longer a fixed word in the markup.
     */
    public function test_dispatch_details_badge_is_not_hardcoded(): void
    {
        $vehicle = Vehicle::factory()->create(['agency_id' => $this->agency->id]);

        Dispatch::factory()->create([
            'agency_id' => $this->agency->id,
            'vehicle_id' => $vehicle->id,
            'driver_id' => $this->driver->id,
            'time_in' => null,          // still out in the field
        ]);

        $html = $this->actingAs($this->admin)->get('/dispatch')->getContent();

        $this->assertStringContainsString('data-active="1"', $html,
            'The row must publish whether the dispatch is active, or the details modal cannot know.');
        $this->assertStringContainsString('id="vwStatus"', $html,
            'The details badge must be fillable rather than a fixed word.');
        $this->assertStringNotContainsString('rounded-pill mb-2">Completed<', $html,
            'The details badge is hardcoded to Completed again.');
    }

    public function test_guest_is_redirected_to_login(): void
    {
        $this->get('/dispatch')->assertRedirect(route('login'));
    }

    /**
     * Picking the driver first now helps too (2026-08, lead-reported) — but it
     * cannot always auto-select, because a driver may be the primary driver of
     * more than one vehicle. Each driver option therefore carries the plates it
     * would fill in, and the script decides.
     */
    public function test_driver_options_carry_their_assigned_vehicles(): void
    {
        $driver = User::factory()->driver()->create(['agency_id' => $this->agency->id]);
        $first = Vehicle::factory()->create([
            'agency_id' => $this->agency->id,
            'plate_number' => 'BFP-1111',
            'status' => Vehicle::STATUS_OPERATIONAL,
            'assigned_driver_id' => $driver->id,
        ]);
        $second = Vehicle::factory()->create([
            'agency_id' => $this->agency->id,
            'plate_number' => 'BFP-2222',
            'status' => Vehicle::STATUS_OPERATIONAL,
            'assigned_driver_id' => $driver->id,
        ]);

        $html = $this->actingAs($this->admin)->get('/dispatch')->assertOk()->getContent();

        $this->assertStringContainsString('data-vehicle-ids="'.$first->id.','.$second->id.'"', $html);
        $this->assertStringContainsString('data-vehicle-plates="BFP-1111, BFP-2222"', $html);
    }

    /** A driver with nothing dispatchable must offer nothing, not a stale plate. */
    public function test_a_driver_with_no_operational_vehicle_carries_no_plates(): void
    {
        $driver = User::factory()->driver()->create(['agency_id' => $this->agency->id]);
        Vehicle::factory()->create([
            'agency_id' => $this->agency->id,
            'plate_number' => 'BFP-9999',
            'status' => Vehicle::STATUS_UNDER_PM,
            'assigned_driver_id' => $driver->id,
        ]);

        $html = $this->actingAs($this->admin)->get('/dispatch')->assertOk()->getContent();

        $this->assertStringNotContainsString('BFP-9999', $html);
        $this->assertStringContainsString('data-vehicle-plates=""', $html);
    }
}
