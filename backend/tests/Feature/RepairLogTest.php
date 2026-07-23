<?php

namespace Tests\Feature;

use App\Models\Agency;
use App\Models\RepairLog;
use App\Models\User;
use App\Models\Vehicle;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/**
 * R4 — Repair logs API (FR-13): admins log repairs against their own agency's
 * vehicles with a source; External Repair Shop requires the shop name; drivers
 * cannot access; cross-agency access 404s.
 */
class RepairLogTest extends TestCase
{
    use RefreshDatabase;

    private function adminWithVehicle(string $code = 'BFP'): array
    {
        $agency = Agency::factory()->create(['code' => $code]);
        $admin = User::factory()->admin()->create(['agency_id' => $agency->id]);
        $vehicle = Vehicle::factory()->create(['agency_id' => $agency->id]);

        return [$agency, $admin, $vehicle];
    }

    public function test_admin_logs_an_internal_repair(): void
    {
        [$agency, $admin, $vehicle] = $this->adminWithVehicle();
        Sanctum::actingAs($admin);

        $response = $this->postJson('/api/v1/repairs', [
            'vehicle_id' => $vehicle->id,
            'repair_date' => now()->toDateString(),
            'scope_of_work' => 'Replace brake pads',
            'cost' => 3500.50,
            'repair_source' => RepairLog::SOURCE_INTERNAL,
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.repair_source', RepairLog::SOURCE_INTERNAL)
            ->assertJsonPath('data.scope_of_work', 'Replace brake pads');

        $this->assertDatabaseHas('repair_logs', [
            'vehicle_id' => $vehicle->id,
            'agency_id' => $agency->id,
            'repair_source' => RepairLog::SOURCE_INTERNAL,
        ]);
    }

    public function test_external_source_requires_a_shop_name(): void
    {
        [, $admin, $vehicle] = $this->adminWithVehicle();
        Sanctum::actingAs($admin);

        $this->postJson('/api/v1/repairs', [
            'vehicle_id' => $vehicle->id,
            'repair_date' => now()->toDateString(),
            'scope_of_work' => 'Engine overhaul',
            'repair_source' => RepairLog::SOURCE_EXTERNAL,
        ])->assertStatus(422)->assertJsonValidationErrors('external_shop_name');
    }

    public function test_external_source_with_shop_name_succeeds(): void
    {
        [, $admin, $vehicle] = $this->adminWithVehicle();
        Sanctum::actingAs($admin);

        $this->postJson('/api/v1/repairs', [
            'vehicle_id' => $vehicle->id,
            'repair_date' => now()->toDateString(),
            'scope_of_work' => 'Engine overhaul',
            'repair_source' => RepairLog::SOURCE_EXTERNAL,
            'external_shop_name' => 'Calbayog Auto Works',
        ])->assertCreated()->assertJsonPath('data.external_shop_name', 'Calbayog Auto Works');
    }

    public function test_scope_of_work_is_required(): void
    {
        [, $admin, $vehicle] = $this->adminWithVehicle();
        Sanctum::actingAs($admin);

        $this->postJson('/api/v1/repairs', [
            'vehicle_id' => $vehicle->id,
            'repair_date' => now()->toDateString(),
            'repair_source' => RepairLog::SOURCE_INTERNAL,
        ])->assertStatus(422)->assertJsonValidationErrors('scope_of_work');
    }

    public function test_admin_updates_a_repair(): void
    {
        [$agency, $admin, $vehicle] = $this->adminWithVehicle();
        $repair = RepairLog::factory()->create(['agency_id' => $agency->id, 'vehicle_id' => $vehicle->id]);
        Sanctum::actingAs($admin);

        $this->putJson("/api/v1/repairs/{$repair->id}", [
            'vehicle_id' => $vehicle->id,
            'repair_date' => now()->toDateString(),
            'scope_of_work' => 'Updated scope',
            'repair_source' => RepairLog::SOURCE_GSO,
        ])->assertOk()->assertJsonPath('data.scope_of_work', 'Updated scope');

        $this->assertSame('Updated scope', $repair->fresh()->scope_of_work);
    }

    public function test_driver_cannot_access_repairs(): void
    {
        [$agency] = $this->adminWithVehicle();
        Sanctum::actingAs(User::factory()->driver()->create(['agency_id' => $agency->id]));

        $this->getJson('/api/v1/repairs')->assertForbidden();
        $this->postJson('/api/v1/repairs', [])->assertForbidden();
    }

    public function test_only_own_agency_repairs_are_listed_and_cross_agency_404s(): void
    {
        [$agency, $admin, $vehicle] = $this->adminWithVehicle('BFP');
        RepairLog::factory()->create(['agency_id' => $agency->id, 'vehicle_id' => $vehicle->id]);

        $foreignAgency = Agency::factory()->create(['code' => 'PNP']);
        $foreign = RepairLog::factory()->create([
            'agency_id' => $foreignAgency->id,
            'vehicle_id' => Vehicle::factory()->create(['agency_id' => $foreignAgency->id])->id,
        ]);

        Sanctum::actingAs($admin);

        $this->getJson('/api/v1/repairs')->assertOk()->assertJsonCount(1, 'data');
        $this->getJson("/api/v1/repairs/{$foreign->id}")->assertNotFound();
    }

    public function test_cannot_log_against_another_agencys_vehicle(): void
    {
        [, $admin] = $this->adminWithVehicle('BFP');
        $foreignVehicle = Vehicle::factory()->create([
            'agency_id' => Agency::factory()->create(['code' => 'PNP'])->id,
        ]);
        Sanctum::actingAs($admin);

        $this->postJson('/api/v1/repairs', [
            'vehicle_id' => $foreignVehicle->id,
            'repair_date' => now()->toDateString(),
            'scope_of_work' => 'x',
            'repair_source' => RepairLog::SOURCE_INTERNAL,
        ])->assertStatus(422)->assertJsonValidationErrors('vehicle_id');
    }
}
