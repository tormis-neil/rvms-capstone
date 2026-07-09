<?php

namespace Tests\Feature;

use App\Models\Agency;
use App\Models\User;
use App\Models\Vehicle;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/**
 * R2 — /api/v1/vehicles (FR-05, FR-18): shapes, validation, role gating,
 * agency isolation, plate-unique-per-agency, same-agency driver rule.
 */
class VehicleApiTest extends TestCase
{
    use RefreshDatabase;

    private Agency $agency;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->agency = Agency::factory()->create();
        $this->admin = User::factory()->admin()->create(['agency_id' => $this->agency->id]);
    }

    private function actingAsAdmin(): void
    {
        Sanctum::actingAs($this->admin);
    }

    private function validPayload(array $overrides = []): array
    {
        return $overrides + [
            'type' => 'Fire Truck',
            'plate_number' => 'ABC-1234',
            'make' => 'Isuzu',
            'model' => 'FTR 850',
            'engine_number' => '4HK1-TC-587234',
            'chassis_number' => 'JALC4W14697100345',
            'current_mileage' => 45230,
        ];
    }

    public function test_index_returns_only_own_agency_vehicles(): void
    {
        Vehicle::factory()->count(2)->create(['agency_id' => $this->agency->id]);
        Vehicle::factory()->create(); // other agency

        $this->actingAsAdmin();

        $this->getJson('/api/v1/vehicles')
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonStructure(['data' => [['id', 'plate_number', 'type', 'make', 'model', 'current_mileage', 'status']]]);
    }

    public function test_store_creates_vehicle_stamped_with_admin_agency(): void
    {
        $this->actingAsAdmin();

        $response = $this->postJson('/api/v1/vehicles', $this->validPayload())
            ->assertCreated()
            ->assertJsonPath('data.plate_number', 'ABC-1234')
            ->assertJsonPath('data.status', Vehicle::STATUS_OPERATIONAL);

        $this->assertSame($this->agency->id, Vehicle::withoutGlobalScopes()->findOrFail($response->json('data.id'))->agency_id);
    }

    public function test_store_rejects_missing_required_fields(): void
    {
        $this->actingAsAdmin();

        $this->postJson('/api/v1/vehicles', [])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['type', 'plate_number', 'make', 'model', 'current_mileage']);
    }

    public function test_plate_number_must_be_unique_within_the_agency(): void
    {
        Vehicle::factory()->create(['agency_id' => $this->agency->id, 'plate_number' => 'ABC-1234']);

        $this->actingAsAdmin();

        $this->postJson('/api/v1/vehicles', $this->validPayload())
            ->assertStatus(422)
            ->assertJsonValidationErrors(['plate_number']);
    }

    public function test_same_plate_is_allowed_in_a_different_agency(): void
    {
        Vehicle::factory()->create(['plate_number' => 'ABC-1234']); // other agency

        $this->actingAsAdmin();

        $this->postJson('/api/v1/vehicles', $this->validPayload())->assertCreated();
    }

    public function test_assigned_driver_must_belong_to_the_same_agency(): void
    {
        $foreignDriver = User::factory()->driver()->create(); // other agency

        $this->actingAsAdmin();

        $this->postJson('/api/v1/vehicles', $this->validPayload(['assigned_driver_id' => $foreignDriver->id]))
            ->assertStatus(422)
            ->assertJsonValidationErrors(['assigned_driver_id']);
    }

    public function test_assigned_driver_cannot_be_an_admin(): void
    {
        $this->actingAsAdmin();

        $this->postJson('/api/v1/vehicles', $this->validPayload(['assigned_driver_id' => $this->admin->id]))
            ->assertStatus(422)
            ->assertJsonValidationErrors(['assigned_driver_id']);
    }

    public function test_show_and_update_work_for_own_vehicle(): void
    {
        $driver = User::factory()->driver()->create(['agency_id' => $this->agency->id]);
        $vehicle = Vehicle::factory()->create(['agency_id' => $this->agency->id]);

        $this->actingAsAdmin();

        $this->getJson("/api/v1/vehicles/{$vehicle->id}")->assertOk();

        $this->putJson("/api/v1/vehicles/{$vehicle->id}", $this->validPayload([
            'plate_number' => 'NEW-9999',
            'assigned_driver_id' => $driver->id,
        ]))
            ->assertOk()
            ->assertJsonPath('data.plate_number', 'NEW-9999')
            ->assertJsonPath('data.assigned_driver.id', $driver->id);
    }

    public function test_cross_agency_vehicle_returns_404(): void
    {
        $foreign = Vehicle::factory()->create(); // other agency

        $this->actingAsAdmin();

        $this->getJson("/api/v1/vehicles/{$foreign->id}")->assertNotFound();
        $this->putJson("/api/v1/vehicles/{$foreign->id}", $this->validPayload())->assertNotFound();
        $this->patchJson("/api/v1/vehicles/{$foreign->id}/status", ['status' => 'Operational'])->assertNotFound();
    }

    public function test_status_accepts_each_enum_value_and_rejects_others(): void
    {
        $vehicle = Vehicle::factory()->create(['agency_id' => $this->agency->id]);

        $this->actingAsAdmin();

        foreach (Vehicle::STATUSES as $status) {
            $this->patchJson("/api/v1/vehicles/{$vehicle->id}/status", ['status' => $status])
                ->assertOk()
                ->assertJsonPath('data.status', $status);
        }

        $this->patchJson("/api/v1/vehicles/{$vehicle->id}/status", ['status' => 'Broken'])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['status']);
    }

    public function test_status_update_accepts_optional_remarks_and_overwrites_on_each_change(): void
    {
        $vehicle = Vehicle::factory()->create(['agency_id' => $this->agency->id]);

        $this->actingAsAdmin();

        $this->patchJson("/api/v1/vehicles/{$vehicle->id}/status", [
            'status' => Vehicle::STATUS_UNDER_PM,
            'remarks' => 'Sent for brake inspection.',
        ])->assertOk()->assertJsonPath('data.remarks', 'Sent for brake inspection.');

        // Omitting remarks entirely (e.g. the API caller does not send the key)
        // leaves the existing note untouched — no history, but no silent wipe either.
        $this->patchJson("/api/v1/vehicles/{$vehicle->id}/status", [
            'status' => Vehicle::STATUS_OPERATIONAL,
        ])->assertOk()->assertJsonPath('data.remarks', 'Sent for brake inspection.');

        // An explicit empty value overwrites/clears the note (like current_mileage).
        $this->patchJson("/api/v1/vehicles/{$vehicle->id}/status", [
            'status' => Vehicle::STATUS_NOT_OPERATIONAL,
            'remarks' => '',
        ])->assertOk()->assertJsonPath('data.remarks', null);
    }

    public function test_driver_token_is_refused_on_admin_vehicle_routes(): void
    {
        $driver = User::factory()->driver()->create(['agency_id' => $this->agency->id]);
        $vehicle = Vehicle::factory()->create(['agency_id' => $this->agency->id]);

        Sanctum::actingAs($driver);

        $this->getJson('/api/v1/vehicles')->assertForbidden();
        $this->postJson('/api/v1/vehicles', $this->validPayload())->assertForbidden();
        $this->patchJson("/api/v1/vehicles/{$vehicle->id}/status", ['status' => 'Operational'])->assertForbidden();
    }

    public function test_unauthenticated_requests_get_401(): void
    {
        $this->getJson('/api/v1/vehicles')->assertUnauthorized();
        $this->postJson('/api/v1/vehicles', $this->validPayload())->assertUnauthorized();
    }
}
