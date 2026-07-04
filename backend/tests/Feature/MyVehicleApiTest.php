<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Vehicle;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class MyVehicleApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_driver_gets_their_assigned_vehicle(): void
    {
        $driver = User::factory()->driver()->create();
        $vehicle = Vehicle::factory()->create([
            'agency_id' => $driver->agency_id,
            'assigned_driver_id' => $driver->id,
        ]);

        Sanctum::actingAs($driver);

        $this->getJson('/api/v1/my-vehicle')
            ->assertOk()
            ->assertJsonPath('data.id', $vehicle->id)
            ->assertJsonPath('data.plate_number', $vehicle->plate_number);
    }

    public function test_driver_with_no_assignment_gets_null_payload(): void
    {
        Sanctum::actingAs(User::factory()->driver()->create());

        $this->getJson('/api/v1/my-vehicle')
            ->assertOk()
            ->assertJsonPath('data', null);
    }

    public function test_admin_token_is_rejected(): void
    {
        Sanctum::actingAs(User::factory()->admin()->create());

        $this->getJson('/api/v1/my-vehicle')->assertStatus(403);
    }

    public function test_unauthenticated_is_401(): void
    {
        $this->getJson('/api/v1/my-vehicle')->assertStatus(401);
    }

    public function test_driver_does_not_receive_another_drivers_vehicle(): void
    {
        $driver = User::factory()->driver()->create();
        // a vehicle in the same agency assigned to someone else
        Vehicle::factory()->create([
            'agency_id' => $driver->agency_id,
            'assigned_driver_id' => User::factory()->driver()->create(['agency_id' => $driver->agency_id])->id,
        ]);

        Sanctum::actingAs($driver);

        $this->getJson('/api/v1/my-vehicle')->assertOk()->assertJsonPath('data', null);
    }
}
