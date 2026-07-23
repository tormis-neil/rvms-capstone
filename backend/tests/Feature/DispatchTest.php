<?php

namespace Tests\Feature;

use App\Models\Agency;
use App\Models\Dispatch;
use App\Models\User;
use App\Models\Vehicle;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/**
 * R6 — Dispatch API (FR-15, FR-16, FR-17): opening sets the vehicle Dispatched;
 * "Others" needs a detail; closing writes the return status and (optionally) a
 * time-in odometer that bumps the vehicle's mileage; availability is
 * agency-scoped; drivers cannot access; cross-agency 404s.
 */
class DispatchTest extends TestCase
{
    use RefreshDatabase;

    private function adminSetup(string $code = 'BFP', int $mileage = 45000): array
    {
        $agency = Agency::factory()->create(['code' => $code]);
        $admin = User::factory()->admin()->create(['agency_id' => $agency->id]);
        $driver = User::factory()->driver()->create(['agency_id' => $agency->id]);
        $vehicle = Vehicle::factory()->create(['agency_id' => $agency->id, 'current_mileage' => $mileage]);

        return [$agency, $admin, $driver, $vehicle];
    }

    private function openPayload(int $vehicleId, int $driverId, array $overrides = []): array
    {
        return array_merge([
            'vehicle_id' => $vehicleId,
            'driver_id' => $driverId,
            'mission_type' => 'Patrol',
            'location' => 'Downtown',
            'time_out' => now()->toDateTimeString(),
        ], $overrides);
    }

    public function test_opening_a_dispatch_sets_the_vehicle_dispatched(): void
    {
        [, $admin, $driver, $vehicle] = $this->adminSetup();
        Sanctum::actingAs($admin);

        $this->postJson('/api/v1/dispatches', $this->openPayload($vehicle->id, $driver->id, [
            'odometer_out' => 45000,
        ]))->assertCreated()->assertJsonPath('data.is_active', true);

        $this->assertSame(Vehicle::STATUS_DISPATCHED, $vehicle->fresh()->status);
    }

    public function test_others_mission_requires_a_detail(): void
    {
        [, $admin, $driver, $vehicle] = $this->adminSetup();
        Sanctum::actingAs($admin);

        $this->postJson('/api/v1/dispatches', $this->openPayload($vehicle->id, $driver->id, [
            'mission_type' => 'Others',
        ]))->assertStatus(422)->assertJsonValidationErrors('mission_other');
    }

    public function test_closing_writes_the_return_status_to_the_vehicle(): void
    {
        [$agency, $admin, $driver, $vehicle] = $this->adminSetup();
        $dispatch = Dispatch::factory()->create([
            'agency_id' => $agency->id, 'vehicle_id' => $vehicle->id, 'driver_id' => $driver->id,
        ]);
        Sanctum::actingAs($admin);

        $this->patchJson("/api/v1/dispatches/{$dispatch->id}/close", [
            'time_in' => now()->toDateTimeString(),
            'return_status' => Vehicle::STATUS_UNDER_PM,
        ])->assertOk()->assertJsonPath('data.is_active', false);

        $this->assertSame(Vehicle::STATUS_UNDER_PM, $vehicle->fresh()->status);
    }

    public function test_closing_with_a_higher_odometer_bumps_the_vehicle_mileage(): void
    {
        [$agency, $admin, $driver, $vehicle] = $this->adminSetup(mileage: 45000);
        $dispatch = Dispatch::factory()->create([
            'agency_id' => $agency->id, 'vehicle_id' => $vehicle->id, 'driver_id' => $driver->id,
        ]);
        Sanctum::actingAs($admin);

        $this->patchJson("/api/v1/dispatches/{$dispatch->id}/close", [
            'time_in' => now()->toDateTimeString(),
            'return_status' => Vehicle::STATUS_OPERATIONAL,
            'odometer_in' => 45380,
        ])->assertOk();

        $this->assertSame(45380, $vehicle->fresh()->current_mileage);
    }

    public function test_closing_with_a_lower_odometer_does_not_reduce_mileage(): void
    {
        [$agency, $admin, $driver, $vehicle] = $this->adminSetup(mileage: 45000);
        $dispatch = Dispatch::factory()->create([
            'agency_id' => $agency->id, 'vehicle_id' => $vehicle->id, 'driver_id' => $driver->id,
        ]);
        Sanctum::actingAs($admin);

        $this->patchJson("/api/v1/dispatches/{$dispatch->id}/close", [
            'time_in' => now()->toDateTimeString(),
            'return_status' => Vehicle::STATUS_OPERATIONAL,
            'odometer_in' => 44000,
        ])->assertOk();

        $this->assertSame(45000, $vehicle->fresh()->current_mileage);
    }

    public function test_odometer_is_optional_on_open_and_close(): void
    {
        [$agency, $admin, $driver, $vehicle] = $this->adminSetup();
        Sanctum::actingAs($admin);

        $open = $this->postJson('/api/v1/dispatches', $this->openPayload($vehicle->id, $driver->id))
            ->assertCreated();

        $this->patchJson("/api/v1/dispatches/{$open->json('data.id')}/close", [
            'time_in' => now()->toDateTimeString(),
            'return_status' => Vehicle::STATUS_OPERATIONAL,
        ])->assertOk()->assertJsonPath('data.odometer_in', null);
    }

    public function test_availability_lists_only_own_agency_vehicles(): void
    {
        [$agency, $admin] = $this->adminSetup('BFP');
        Vehicle::factory()->create(['agency_id' => $agency->id]); // 2 total for BFP

        $other = Agency::factory()->create(['code' => 'PNP']);
        Vehicle::factory()->create(['agency_id' => $other->id]);

        Sanctum::actingAs($admin);

        $this->getJson('/api/v1/vehicles/availability')
            ->assertOk()
            ->assertJsonCount(2, 'data'); // only BFP's two vehicles
    }

    public function test_driver_cannot_access_dispatches(): void
    {
        [$agency] = $this->adminSetup();
        Sanctum::actingAs(User::factory()->driver()->create(['agency_id' => $agency->id]));

        $this->getJson('/api/v1/dispatches')->assertForbidden();
        $this->postJson('/api/v1/dispatches', [])->assertForbidden();
    }

    public function test_cross_agency_dispatch_is_not_found(): void
    {
        [, $admin] = $this->adminSetup('BFP');
        $foreignAgency = Agency::factory()->create(['code' => 'PNP']);
        $foreign = Dispatch::factory()->create([
            'agency_id' => $foreignAgency->id,
            'vehicle_id' => Vehicle::factory()->create(['agency_id' => $foreignAgency->id])->id,
            'driver_id' => User::factory()->driver()->create(['agency_id' => $foreignAgency->id])->id,
        ]);

        Sanctum::actingAs($admin);

        $this->getJson("/api/v1/dispatches/{$foreign->id}")->assertNotFound();
        $this->patchJson("/api/v1/dispatches/{$foreign->id}/close", [])->assertNotFound();
    }
}
