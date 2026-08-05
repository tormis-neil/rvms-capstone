<?php

namespace Tests\Feature;

use App\Models\Agency;
use App\Models\DamageReport;
use App\Models\Dispatch;
use App\Models\Inspection;
use App\Models\PmSchedule;
use App\Models\RepairLog;
use App\Models\User;
use App\Models\Vehicle;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/**
 * Deleting and restoring vehicles and drivers (FR-05, FR-06 — extended 2026-08).
 *
 * The feature is a soft delete for one reason above all others, and the first
 * two tests here are the ones that prove it: every child foreign key declares
 * `cascadeOnDelete()`, so a hard delete would silently take a vehicle's entire
 * maintenance history with it. If these ever fail, the delete button has become
 * a shredder.
 */
class RecordDeletionTest extends TestCase
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
        $this->driver = User::factory()->driver()->create([
            'agency_id' => $this->agency->id,
            'name' => 'Ramon Villanueva',
        ]);
        $this->vehicle = Vehicle::factory()->create([
            'agency_id' => $this->agency->id,
            'plate_number' => 'BFP-0001',
            'assigned_driver_id' => $this->driver->id,
        ]);
    }

    /* ------------------------- history must survive ----------------------- */

    /** The whole reason this is a soft delete. */
    public function test_deleting_a_vehicle_keeps_every_record_attached_to_it(): void
    {
        $inspection = Inspection::factory()->create(['agency_id' => $this->agency->id, 'vehicle_id' => $this->vehicle->id, 'driver_id' => $this->driver->id]);
        $damage = DamageReport::factory()->create(['agency_id' => $this->agency->id, 'vehicle_id' => $this->vehicle->id, 'driver_id' => $this->driver->id]);
        $repair = RepairLog::factory()->create(['agency_id' => $this->agency->id, 'vehicle_id' => $this->vehicle->id]);
        $pm = PmSchedule::factory()->create(['agency_id' => $this->agency->id, 'vehicle_id' => $this->vehicle->id]);

        Sanctum::actingAs($this->admin);
        $this->deleteJson("/api/v1/vehicles/{$this->vehicle->id}")->assertOk();

        $this->assertSoftDeleted('vehicles', ['id' => $this->vehicle->id]);

        // Not one child row was taken with it.
        $this->assertDatabaseHas('inspections', ['id' => $inspection->id]);
        $this->assertDatabaseHas('damage_reports', ['id' => $damage->id]);
        $this->assertDatabaseHas('repair_logs', ['id' => $repair->id]);
        $this->assertDatabaseHas('pm_schedules', ['id' => $pm->id]);
    }

    public function test_deleting_a_driver_keeps_everything_they_filed(): void
    {
        $inspection = Inspection::factory()->create(['agency_id' => $this->agency->id, 'vehicle_id' => $this->vehicle->id, 'driver_id' => $this->driver->id]);
        $damage = DamageReport::factory()->create(['agency_id' => $this->agency->id, 'vehicle_id' => $this->vehicle->id, 'driver_id' => $this->driver->id]);

        Sanctum::actingAs($this->admin);
        $this->deleteJson("/api/v1/drivers/{$this->driver->id}")->assertOk();

        $this->assertSoftDeleted('users', ['id' => $this->driver->id]);
        $this->assertDatabaseHas('inspections', ['id' => $inspection->id, 'driver_id' => $this->driver->id]);
        $this->assertDatabaseHas('damage_reports', ['id' => $damage->id, 'driver_id' => $this->driver->id]);
    }

    /**
     * A blank name on every historical row would make the history useless, so
     * the relations read through the soft delete.
     */
    public function test_a_deleted_drivers_name_still_shows_on_their_past_records(): void
    {
        $inspection = Inspection::factory()->create(['agency_id' => $this->agency->id, 'vehicle_id' => $this->vehicle->id, 'driver_id' => $this->driver->id]);

        app(\App\Services\RecordDeletion::class)->deleteDriver($this->driver);

        $this->assertSame('Ramon Villanueva', $inspection->fresh()->driver->name);
        $this->assertSame('BFP-0001', $inspection->fresh()->vehicle->plate_number);
    }

    public function test_a_deleted_vehicles_plate_still_shows_on_its_past_records(): void
    {
        $repair = RepairLog::factory()->create(['agency_id' => $this->agency->id, 'vehicle_id' => $this->vehicle->id]);

        app(\App\Services\RecordDeletion::class)->deleteVehicle($this->vehicle);

        $this->assertSame('BFP-0001', $repair->fresh()->vehicle->plate_number);
    }

    /* ---------------------------- disappearance --------------------------- */

    public function test_a_deleted_vehicle_leaves_the_list_and_cannot_be_fetched(): void
    {
        Sanctum::actingAs($this->admin);
        $this->deleteJson("/api/v1/vehicles/{$this->vehicle->id}")->assertOk();

        $this->getJson('/api/v1/vehicles')->assertOk()->assertJsonCount(0, 'data');
        $this->getJson("/api/v1/vehicles/{$this->vehicle->id}")->assertNotFound();
    }

    public function test_a_deleted_driver_leaves_the_list_and_cannot_log_in(): void
    {
        $this->driver->update(['password' => 'password']);

        Sanctum::actingAs($this->admin);
        $this->deleteJson("/api/v1/drivers/{$this->driver->id}")->assertOk();

        $this->getJson('/api/v1/drivers')->assertOk()->assertJsonCount(0, 'data');

        // A deleted account is not an account.
        $this->postJson('/api/v1/login', [
            'email' => $this->driver->email,
            'password' => 'password',
        ])->assertStatus(422);
    }

    /** A live bearer token must not outlive the account it belongs to. */
    public function test_deleting_a_driver_revokes_their_tokens_and_device(): void
    {
        $this->driver->update(['fcm_token' => 'device-abc']);
        $this->driver->createToken('phone');
        $this->assertSame(1, $this->driver->tokens()->count());

        app(\App\Services\RecordDeletion::class)->deleteDriver($this->driver);

        $this->assertSame(0, $this->driver->tokens()->count());
        $this->assertNull($this->driver->fresh()->fcm_token);
    }

    /** Vehicles must not point at a driver who is gone. */
    public function test_deleting_a_driver_releases_their_vehicles(): void
    {
        Sanctum::actingAs($this->admin);
        $this->deleteJson("/api/v1/drivers/{$this->driver->id}")->assertOk();

        $this->assertNull($this->vehicle->fresh()->assigned_driver_id);
    }

    /* ------------------------------- guards ------------------------------- */

    public function test_a_vehicle_on_an_active_dispatch_cannot_be_deleted(): void
    {
        Dispatch::factory()->create([
            'agency_id' => $this->agency->id,
            'vehicle_id' => $this->vehicle->id,
            'driver_id' => $this->driver->id,
            'location' => 'Brgy. Payahan',
            'time_in' => null,
        ]);

        Sanctum::actingAs($this->admin);
        $this->deleteJson("/api/v1/vehicles/{$this->vehicle->id}")
            ->assertStatus(422)
            ->assertJsonValidationErrors('vehicle');

        $this->assertNotSoftDeleted('vehicles', ['id' => $this->vehicle->id]);
    }

    /** The refusal must say where to go, like every other dispatch refusal. */
    public function test_the_refusal_names_the_mission_and_points_at_dispatch_logs(): void
    {
        Dispatch::factory()->create([
            'agency_id' => $this->agency->id,
            'vehicle_id' => $this->vehicle->id,
            'driver_id' => $this->driver->id,
            'location' => 'Brgy. Payahan',
            'time_in' => null,
        ]);

        Sanctum::actingAs($this->admin);
        $message = $this->deleteJson("/api/v1/vehicles/{$this->vehicle->id}")
            ->assertStatus(422)
            ->json('errors.vehicle.0');

        $this->assertStringContainsString('Brgy. Payahan', $message);
        $this->assertStringContainsString('Dispatch Logs', $message);
    }

    public function test_a_driver_on_an_active_dispatch_cannot_be_deleted(): void
    {
        Dispatch::factory()->create([
            'agency_id' => $this->agency->id,
            'vehicle_id' => $this->vehicle->id,
            'driver_id' => $this->driver->id,
            'time_in' => null,
        ]);

        Sanctum::actingAs($this->admin);
        $this->deleteJson("/api/v1/drivers/{$this->driver->id}")
            ->assertStatus(422)
            ->assertJsonValidationErrors('driver');

        $this->assertNotSoftDeleted('users', ['id' => $this->driver->id]);
    }

    /** A CLOSED dispatch is history, not a reason to refuse. */
    public function test_a_closed_dispatch_does_not_block_deletion(): void
    {
        Dispatch::factory()->create([
            'agency_id' => $this->agency->id,
            'vehicle_id' => $this->vehicle->id,
            'driver_id' => $this->driver->id,
            'time_in' => now(),
        ]);

        Sanctum::actingAs($this->admin);
        $this->deleteJson("/api/v1/vehicles/{$this->vehicle->id}")->assertOk();

        $this->assertSoftDeleted('vehicles', ['id' => $this->vehicle->id]);
    }

    /* ------------------------------- restore ------------------------------ */

    public function test_a_deleted_vehicle_can_be_restored_with_its_history(): void
    {
        $inspection = Inspection::factory()->create(['agency_id' => $this->agency->id, 'vehicle_id' => $this->vehicle->id, 'driver_id' => $this->driver->id]);

        Sanctum::actingAs($this->admin);
        $this->deleteJson("/api/v1/vehicles/{$this->vehicle->id}")->assertOk();
        $this->patchJson("/api/v1/vehicles/{$this->vehicle->id}/restore")->assertOk();

        $this->assertNotSoftDeleted('vehicles', ['id' => $this->vehicle->id]);
        $this->getJson('/api/v1/vehicles')->assertJsonCount(1, 'data');
        $this->assertDatabaseHas('inspections', ['id' => $inspection->id]);
    }

    public function test_a_deleted_driver_can_be_restored(): void
    {
        Sanctum::actingAs($this->admin);
        $this->deleteJson("/api/v1/drivers/{$this->driver->id}")->assertOk();
        $this->patchJson("/api/v1/drivers/{$this->driver->id}/restore")->assertOk();

        $this->assertNotSoftDeleted('users', ['id' => $this->driver->id]);
        $this->getJson('/api/v1/drivers')->assertJsonCount(1, 'data');
    }

    /* --------------------------- agency isolation ------------------------- */

    public function test_another_agencys_vehicle_cannot_be_deleted_or_restored(): void
    {
        $other = Agency::factory()->create(['code' => 'PNP']);
        $foreign = Vehicle::factory()->create(['agency_id' => $other->id]);

        Sanctum::actingAs($this->admin);
        $this->deleteJson("/api/v1/vehicles/{$foreign->id}")->assertNotFound();
        $this->patchJson("/api/v1/vehicles/{$foreign->id}/restore")->assertNotFound();

        $this->assertNotSoftDeleted('vehicles', ['id' => $foreign->id]);
    }

    public function test_another_agencys_driver_cannot_be_deleted_or_restored(): void
    {
        $other = Agency::factory()->create(['code' => 'PNP']);
        $foreign = User::factory()->driver()->create(['agency_id' => $other->id]);

        Sanctum::actingAs($this->admin);
        $this->deleteJson("/api/v1/drivers/{$foreign->id}")->assertNotFound();
        $this->patchJson("/api/v1/drivers/{$foreign->id}/restore")->assertNotFound();

        $this->assertNotSoftDeleted('users', ['id' => $foreign->id]);
    }

    /** An administrator is not a driver, and the driver endpoint must not reach one. */
    public function test_an_admin_cannot_be_deleted_through_the_driver_endpoint(): void
    {
        $colleague = User::factory()->admin()->create(['agency_id' => $this->agency->id]);

        Sanctum::actingAs($this->admin);
        $this->deleteJson("/api/v1/drivers/{$colleague->id}")->assertNotFound();

        $this->assertNotSoftDeleted('users', ['id' => $colleague->id]);
    }

    public function test_a_driver_token_cannot_delete_anything(): void
    {
        Sanctum::actingAs($this->driver);

        $this->deleteJson("/api/v1/vehicles/{$this->vehicle->id}")->assertForbidden();
        $this->deleteJson("/api/v1/drivers/{$this->driver->id}")->assertForbidden();
    }
}
