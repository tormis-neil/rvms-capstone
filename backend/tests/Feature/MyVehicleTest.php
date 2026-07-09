<?php

namespace Tests\Feature;

use App\Models\Agency;
use App\Models\User;
use App\Models\Vehicle;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/**
 * R2 — GET /api/v1/my-vehicle (FR-07): a driver sees ALL vehicles assigned
 * to them (a driver may hold more than one), and nothing else.
 */
class MyVehicleTest extends TestCase
{
    use RefreshDatabase;

    public function test_driver_gets_all_of_their_assigned_vehicles(): void
    {
        $agency = Agency::factory()->create();
        $driver = User::factory()->driver()->create(['agency_id' => $agency->id]);

        Vehicle::factory()->count(2)->create([
            'agency_id' => $agency->id,
            'assigned_driver_id' => $driver->id,
        ]);
        Vehicle::factory()->create(['agency_id' => $agency->id]); // unassigned

        Sanctum::actingAs($driver);

        $this->getJson('/api/v1/my-vehicle')
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonStructure(['data' => [['id', 'plate_number', 'type', 'status', 'current_mileage']]]);
    }

    public function test_driver_with_no_vehicle_gets_an_empty_list(): void
    {
        Sanctum::actingAs(User::factory()->driver()->create());

        $this->getJson('/api/v1/my-vehicle')
            ->assertOk()
            ->assertJsonCount(0, 'data');
    }

    public function test_another_drivers_vehicle_is_not_returned(): void
    {
        $agency = Agency::factory()->create();
        $driver = User::factory()->driver()->create(['agency_id' => $agency->id]);
        $other = User::factory()->driver()->create(['agency_id' => $agency->id]);

        Vehicle::factory()->create(['agency_id' => $agency->id, 'assigned_driver_id' => $other->id]);

        Sanctum::actingAs($driver);

        $this->getJson('/api/v1/my-vehicle')->assertOk()->assertJsonCount(0, 'data');
    }

    public function test_admin_token_is_refused(): void
    {
        Sanctum::actingAs(User::factory()->admin()->create());

        $this->getJson('/api/v1/my-vehicle')->assertForbidden();
    }

    public function test_unauthenticated_request_gets_401(): void
    {
        $this->getJson('/api/v1/my-vehicle')->assertUnauthorized();
    }
}
