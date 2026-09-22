<?php

namespace Tests\Feature;

use App\Models\Agency;
use App\Models\Dispatch;
use App\Models\Inspection;
use App\Models\PmSchedule;
use App\Models\User;
use App\Models\Vehicle;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * rvms:reset-drivers — the one-time pre-go-live cleanup (2026-09). Removes every
 * driver account and the operational test data tied to drivers, while keeping
 * the agencies, the administrators, and (by default) the vehicles.
 */
class ResetDriversCommandTest extends TestCase
{
    use RefreshDatabase;

    private Agency $agency;

    protected function setUp(): void
    {
        parent::setUp();
        $this->agency = Agency::factory()->create();
    }

    public function test_it_removes_drivers_and_keeps_agencies_admins_and_vehicles(): void
    {
        $admin = User::factory()->admin()->create(['agency_id' => $this->agency->id]);
        $driver = User::factory()->driver()->create(['agency_id' => $this->agency->id]);
        $vehicle = Vehicle::factory()->create([
            'agency_id' => $this->agency->id,
            'assigned_driver_id' => $driver->id,
        ]);

        $this->artisan('rvms:reset-drivers', ['--force' => true])->assertSuccessful();

        // Drivers gone; admin and agency kept.
        $this->assertDatabaseMissing('users', ['id' => $driver->id]);
        $this->assertDatabaseHas('users', ['id' => $admin->id]);
        $this->assertDatabaseHas('agencies', ['id' => $this->agency->id]);

        // Vehicle kept, but released from the deleted driver.
        $this->assertDatabaseHas('vehicles', ['id' => $vehicle->id]);
        $this->assertNull($vehicle->fresh()->assigned_driver_id);
        $this->assertNull($vehicle->fresh()->secondary_driver_id);
    }

    public function test_it_clears_operational_test_data(): void
    {
        $driver = User::factory()->driver()->create(['agency_id' => $this->agency->id]);
        $vehicle = Vehicle::factory()->create(['agency_id' => $this->agency->id]);

        Inspection::factory()->create([
            'agency_id' => $this->agency->id,
            'vehicle_id' => $vehicle->id,
            'driver_id' => $driver->id,
        ]);
        PmSchedule::factory()->create([
            'agency_id' => $this->agency->id,
            'vehicle_id' => $vehicle->id,
        ]);
        Dispatch::factory()->create([
            'agency_id' => $this->agency->id,
            'vehicle_id' => $vehicle->id,
            'driver_id' => $driver->id,
        ]);

        $this->artisan('rvms:reset-drivers', ['--force' => true])->assertSuccessful();

        $this->assertDatabaseCount('inspections', 0);
        $this->assertDatabaseCount('pm_schedules', 0);
        $this->assertDatabaseCount('dispatches', 0);
    }

    public function test_with_vehicles_also_deletes_the_vehicles(): void
    {
        User::factory()->admin()->create(['agency_id' => $this->agency->id]);
        Vehicle::factory()->create(['agency_id' => $this->agency->id]);

        $this->artisan('rvms:reset-drivers', ['--force' => true, '--with-vehicles' => true])
            ->assertSuccessful();

        $this->assertDatabaseCount('vehicles', 0);
        // Agencies and admins still stand.
        $this->assertDatabaseHas('agencies', ['id' => $this->agency->id]);
        $this->assertDatabaseCount('users', 1); // the admin
    }

    public function test_declining_the_confirmation_changes_nothing(): void
    {
        $driver = User::factory()->driver()->create(['agency_id' => $this->agency->id]);

        $this->artisan('rvms:reset-drivers')
            ->expectsConfirmation('Proceed with the cleanup?', 'no')
            ->assertSuccessful();

        $this->assertDatabaseHas('users', ['id' => $driver->id]);
    }
}
