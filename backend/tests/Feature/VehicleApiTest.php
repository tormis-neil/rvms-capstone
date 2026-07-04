<?php

namespace Tests\Feature;

use App\Models\Agency;
use App\Models\User;
use App\Models\Vehicle;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class VehicleApiTest extends TestCase
{
    use RefreshDatabase;

    private function admin(?Agency $agency = null): User
    {
        return User::factory()->admin()->create(['agency_id' => ($agency ?? Agency::factory()->create())->id]);
    }

    public function test_admin_can_list_only_own_agency_vehicles(): void
    {
        $admin = $this->admin();
        Vehicle::factory()->count(2)->create(['agency_id' => $admin->agency_id]);
        Vehicle::factory()->create(); // another agency

        Sanctum::actingAs($admin);

        $this->getJson('/api/v1/vehicles')
            ->assertOk()
            ->assertJsonCount(2, 'data');
    }

    public function test_admin_can_create_a_vehicle(): void
    {
        $admin = $this->admin();
        Sanctum::actingAs($admin);

        $this->postJson('/api/v1/vehicles', [
            'type' => 'Fire Truck',
            'plate_number' => 'ABC-123',
            'make' => 'Isuzu',
            'model' => 'FTR',
            'current_mileage' => 1200,
        ])->assertCreated()
            ->assertJsonPath('data.plate_number', 'ABC-123')
            ->assertJsonPath('data.status', Vehicle::STATUS_OPERATIONAL)
            ->assertJsonPath('data.agency_id', $admin->agency_id);
    }

    public function test_create_validation_rejects_bad_payload(): void
    {
        Sanctum::actingAs($this->admin());

        $this->postJson('/api/v1/vehicles', [
            'type' => '',
            'plate_number' => '',
            'current_mileage' => -5,
        ])->assertStatus(422)
            ->assertJsonValidationErrors(['type', 'plate_number', 'make', 'model', 'current_mileage']);
    }

    public function test_status_endpoint_only_accepts_the_four_values(): void
    {
        $admin = $this->admin();
        $vehicle = Vehicle::factory()->create(['agency_id' => $admin->agency_id]);
        Sanctum::actingAs($admin);

        $this->patchJson("/api/v1/vehicles/{$vehicle->id}/status", ['status' => 'Broken'])
            ->assertStatus(422)->assertJsonValidationErrors(['status']);

        $this->patchJson("/api/v1/vehicles/{$vehicle->id}/status", ['status' => Vehicle::STATUS_DISPATCHED])
            ->assertOk()->assertJsonPath('data.status', Vehicle::STATUS_DISPATCHED);
    }

    public function test_unauthenticated_is_401(): void
    {
        $this->getJson('/api/v1/vehicles')->assertStatus(401);
    }

    public function test_driver_token_is_403(): void
    {
        Sanctum::actingAs(User::factory()->driver()->create());
        $this->getJson('/api/v1/vehicles')->assertStatus(403);
    }

    public function test_admin_cannot_read_another_agencys_vehicle(): void
    {
        $foreign = Vehicle::factory()->create();
        Sanctum::actingAs($this->admin());

        $this->getJson("/api/v1/vehicles/{$foreign->id}")->assertStatus(404);
    }

    public function test_admin_cannot_update_another_agencys_vehicle(): void
    {
        $foreign = Vehicle::factory()->create();
        Sanctum::actingAs($this->admin());

        $this->putJson("/api/v1/vehicles/{$foreign->id}", [
            'type' => 'Hijack',
            'plate_number' => 'HIJ-1',
            'make' => 'X',
            'model' => 'Y',
            'current_mileage' => 1,
        ])->assertStatus(404);
    }

    public function test_plate_number_unique_within_agency_but_allowed_across_agencies(): void
    {
        $admin = $this->admin();
        Vehicle::factory()->create(['agency_id' => $admin->agency_id, 'plate_number' => 'DUP-1']);
        // same plate exists in a different agency — should not block
        Vehicle::factory()->create(['plate_number' => 'DUP-1']);

        Sanctum::actingAs($admin);

        $this->postJson('/api/v1/vehicles', [
            'type' => 'Ambulance', 'plate_number' => 'DUP-1', 'make' => 'Toyota',
            'model' => 'Hiace', 'current_mileage' => 0,
        ])->assertStatus(422)->assertJsonValidationErrors(['plate_number']);
    }

    public function test_assigned_driver_must_be_same_agency_driver(): void
    {
        $admin = $this->admin();
        $foreignDriver = User::factory()->driver()->create();
        Sanctum::actingAs($admin);

        $this->postJson('/api/v1/vehicles', [
            'type' => 'Ambulance', 'plate_number' => 'ZZ-1', 'make' => 'Toyota',
            'model' => 'Hiace', 'current_mileage' => 0,
            'assigned_driver_id' => $foreignDriver->id,
        ])->assertStatus(422)->assertJsonValidationErrors(['assigned_driver_id']);
    }
}
