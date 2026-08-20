<?php

namespace Tests\Feature;

use App\Models\Agency;
use App\Models\DamageReport;
use App\Models\Dispatch;
use App\Models\Inspection;
use App\Models\PmSchedule;
use App\Models\User;
use App\Models\Vehicle;
use App\Services\VehicleStatusWriter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/**
 * Design decision 9 — vehicle status change governance.
 *
 * FR-18 allows the shared status to be written "from any applicable module", so
 * every module keeps its control. The one rule that must hold everywhere is
 * that a vehicle on an ACTIVE DISPATCH keeps its Dispatched status until the
 * dispatch is closed (FR-15 → FR-16); otherwise an open mission could be left
 * contradicting the vehicle's status.
 *
 * Every accepted write also records WHERE it came from, so the confirmation
 * dialog can tell the next admin what they are overriding.
 */
class VehicleStatusGovernanceTest extends TestCase
{
    use RefreshDatabase;

    private Agency $agency;

    private User $admin;

    private User $driver;

    private Vehicle $vehicle;

    protected function setUp(): void
    {
        parent::setUp();
        $this->agency = Agency::factory()->create(['code' => 'BFP']);
        $this->admin = User::factory()->admin()->create(['agency_id' => $this->agency->id]);
        $this->driver = User::factory()->driver()->create(['agency_id' => $this->agency->id]);
        $this->vehicle = Vehicle::factory()->create([
            'agency_id' => $this->agency->id,
            'status' => Vehicle::STATUS_OPERATIONAL,
        ]);
    }

    private function openDispatch(): Dispatch
    {
        return Dispatch::factory()->create([
            'agency_id' => $this->agency->id,
            'vehicle_id' => $this->vehicle->id,
            'driver_id' => $this->driver->id,
            'mission_type' => 'Patrol',
            'location' => 'Brgy. Obrero',
            'time_in' => null,
        ]);
    }

    // --- the source stamp -------------------------------------------------

    public function test_a_status_write_records_where_it_came_from(): void
    {
        $this->actingAs($this->admin)
            ->patch("/vehicles/{$this->vehicle->id}/status", ['status' => Vehicle::STATUS_UNDER_PM, 'remarks' => 'Reason for the change.'])
            ->assertRedirect(route('vehicles'));

        $fresh = $this->vehicle->fresh();
        $this->assertSame(VehicleStatusWriter::SOURCE_VEHICLES, $fresh->status_source);
        $this->assertNotNull($fresh->status_changed_at);
        $this->assertStringContainsString('Vehicle Management', $fresh->statusOriginLabel());
    }

    public function test_each_module_stamps_its_own_source(): void
    {
        // Inspection review
        $inspection = Inspection::factory()->create([
            'agency_id' => $this->agency->id,
            'vehicle_id' => $this->vehicle->id,
            'driver_id' => $this->driver->id,
        ]);
        $this->actingAs($this->admin)
            ->patch("/inspections/{$inspection->id}/review", ['vehicle_status' => Vehicle::STATUS_NOT_OPERATIONAL]);
        $this->assertSame(VehicleStatusWriter::SOURCE_INSPECTION, $this->vehicle->fresh()->status_source);

        // Damage review
        $report = DamageReport::factory()->create([
            'agency_id' => $this->agency->id,
            'vehicle_id' => $this->vehicle->id,
            'driver_id' => $this->driver->id,
        ]);
        $this->actingAs($this->admin)
            ->patch("/damage-reports/{$report->id}/review", ['vehicle_status' => Vehicle::STATUS_UNDER_PM]);
        $this->assertSame(VehicleStatusWriter::SOURCE_DAMAGE, $this->vehicle->fresh()->status_source);
    }

    public function test_opening_and_closing_a_dispatch_stamp_their_own_sources(): void
    {
        $this->actingAs($this->admin)->post('/dispatch', [
            'vehicle_id' => $this->vehicle->id,
            'driver_id' => $this->driver->id,
            'mission_type' => 'Patrol',
            'location' => 'Downtown',
            'time_out' => now()->toDateTimeString(),
        ]);
        $this->assertSame(VehicleStatusWriter::SOURCE_DISPATCH_OPEN, $this->vehicle->fresh()->status_source);
        $this->assertSame(Vehicle::STATUS_DISPATCHED, $this->vehicle->fresh()->status);

        $dispatch = Dispatch::withoutGlobalScopes()->latest('id')->first();
        $this->actingAs($this->admin)->patch("/dispatch/{$dispatch->id}/close", [
            'time_in' => now()->toDateTimeString(),
            'return_status' => Vehicle::STATUS_OPERATIONAL,
        ]);
        $this->assertSame(VehicleStatusWriter::SOURCE_DISPATCH_CLOSE, $this->vehicle->fresh()->status_source);
        $this->assertSame(Vehicle::STATUS_OPERATIONAL, $this->vehicle->fresh()->status);
    }

    // --- the active-dispatch guard ---------------------------------------

    public function test_vehicle_management_cannot_restatus_a_vehicle_on_an_active_dispatch(): void
    {
        $this->openDispatch();
        $this->vehicle->update(['status' => Vehicle::STATUS_DISPATCHED]);

        $this->actingAs($this->admin)
            ->from('/vehicles')
            ->patch("/vehicles/{$this->vehicle->id}/status", ['status' => Vehicle::STATUS_OPERATIONAL, 'remarks' => 'Reason for the change.'])
            ->assertSessionHasErrors('status');

        $this->assertSame(Vehicle::STATUS_DISPATCHED, $this->vehicle->fresh()->status);
    }

    public function test_the_block_message_names_the_mission_so_the_admin_knows_where_to_go(): void
    {
        $this->openDispatch();
        $this->vehicle->update(['status' => Vehicle::STATUS_DISPATCHED]);

        Sanctum::actingAs($this->admin);
        $response = $this->patchJson("/api/v1/vehicles/{$this->vehicle->id}/status", [
            'status' => Vehicle::STATUS_OPERATIONAL,
            'remarks' => 'Reason for the change.',
        ])->assertStatus(422);

        $message = $response->json('errors.status.0');
        $this->assertStringContainsString('active dispatch', $message);
        $this->assertStringContainsString('Patrol', $message);
        $this->assertStringContainsString('Brgy. Obrero', $message);
        $this->assertStringContainsString('Close the dispatch', $message);
    }

    public function test_inspection_review_cannot_restatus_a_dispatched_vehicle(): void
    {
        $this->openDispatch();
        $this->vehicle->update(['status' => Vehicle::STATUS_DISPATCHED]);

        $inspection = Inspection::factory()->create([
            'agency_id' => $this->agency->id,
            'vehicle_id' => $this->vehicle->id,
            'driver_id' => $this->driver->id,
        ]);

        $this->actingAs($this->admin)
            ->from('/inspections')
            ->patch("/inspections/{$inspection->id}/review", ['vehicle_status' => Vehicle::STATUS_OPERATIONAL])
            ->assertSessionHasErrors('status');

        $this->assertSame(Vehicle::STATUS_DISPATCHED, $this->vehicle->fresh()->status);
    }

    public function test_damage_review_cannot_restatus_a_dispatched_vehicle(): void
    {
        $this->openDispatch();
        $this->vehicle->update(['status' => Vehicle::STATUS_DISPATCHED]);

        $report = DamageReport::factory()->create([
            'agency_id' => $this->agency->id,
            'vehicle_id' => $this->vehicle->id,
            'driver_id' => $this->driver->id,
        ]);

        $this->actingAs($this->admin)
            ->from('/inspections')
            ->patch("/damage-reports/{$report->id}/review", ['vehicle_status' => Vehicle::STATUS_OPERATIONAL])
            ->assertSessionHasErrors('status');

        $this->assertSame(Vehicle::STATUS_DISPATCHED, $this->vehicle->fresh()->status);
    }

    public function test_closing_the_dispatch_releases_the_vehicle_and_restores_normal_writes(): void
    {
        $dispatch = $this->openDispatch();
        $this->vehicle->update(['status' => Vehicle::STATUS_DISPATCHED]);

        // Closing is the sanctioned exit (FR-16).
        $this->actingAs($this->admin)->patch("/dispatch/{$dispatch->id}/close", [
            'time_in' => now()->toDateTimeString(),
            'return_status' => Vehicle::STATUS_NOT_OPERATIONAL,
        ])->assertRedirect(route('dispatch'));

        $this->assertSame(Vehicle::STATUS_NOT_OPERATIONAL, $this->vehicle->fresh()->status);

        // With no open dispatch, other modules may write again.
        $this->actingAs($this->admin)
            ->patch("/vehicles/{$this->vehicle->id}/status", ['status' => Vehicle::STATUS_OPERATIONAL, 'remarks' => 'Reason for the change.'])
            ->assertRedirect(route('vehicles'));

        $this->assertSame(Vehicle::STATUS_OPERATIONAL, $this->vehicle->fresh()->status);
    }

    // --- the new controls that remove the dead ends -----------------------

    public function test_pm_and_reviewed_rows_expose_an_update_status_control(): void
    {
        PmSchedule::factory()->create([
            'agency_id' => $this->agency->id,
            'vehicle_id' => $this->vehicle->id,
        ]);

        // PM Schedules used to have no status control at all, stranding a
        // vehicle it had put Under Preventive Maintenance.
        $this->actingAs($this->admin)
            ->get('/pm')
            ->assertOk()
            ->assertSee('Update Vehicle Status')
            ->assertSee('id="updateStatusModal"', false);

        DamageReport::factory()->reviewed()->create([
            'agency_id' => $this->agency->id,
            'vehicle_id' => $this->vehicle->id,
            'driver_id' => $this->driver->id,
        ]);

        $this->actingAs($this->admin)
            ->get('/inspections')
            ->assertOk()
            ->assertSee('Update Vehicle Status')
            ->assertSee('View Damage Report');
    }
}
