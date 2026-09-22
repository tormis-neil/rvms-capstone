<?php

namespace Tests\Feature;

use App\Models\Agency;
use App\Models\PmSchedule;
use App\Models\User;
use App\Models\Vehicle;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/**
 * Adviser/agency consultation changes, 2026-09 (CHO + PNP):
 *   F2 — optional supporting document when a PM schedule is CREATED (FR-14).
 *   F3 — optional travel-order document when a dispatch is OPENED (FR-15).
 *   F4 — driver TESDA NC II number/expiry, monitored like the licence (FR-08/FR-10).
 *   F5 — optional SECONDARY driver on a vehicle (FR-07/FR-08/FR-09).
 *
 * Every one of these is OPTIONAL, so the existing single-value flows must keep
 * working untouched — the tests assert both the new capability and that its
 * absence changes nothing.
 */
class AdviserRecommendations2026Test extends TestCase
{
    use RefreshDatabase;

    private Agency $agency;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');

        $this->agency = Agency::factory()->create(['code' => 'CHO']);
        $this->admin = User::factory()->admin()->create(['agency_id' => $this->agency->id]);
    }

    /* ============================ F2 — PM document ============================ */

    private function mileagePmPayload(array $overrides = []): array
    {
        $vehicle = Vehicle::factory()->create(['agency_id' => $this->agency->id, 'current_mileage' => 10_000]);

        return array_merge([
            'vehicle_id' => $vehicle->id,
            'service_target' => 'Oil Change & Filter',
            'pm_type' => PmSchedule::TYPE_MILEAGE,
            'interval_km' => 5_000,
            'last_pm_mileage' => 10_000,
            'due_soon_threshold_km' => 500,
        ], $overrides);
    }

    public function test_a_pm_schedule_can_be_created_with_a_supporting_document(): void
    {
        Sanctum::actingAs($this->admin);

        $this->postJson('/api/v1/pm-schedules', $this->mileagePmPayload([
            'schedule_document' => UploadedFile::fake()->create('pre-inspection.pdf', 200, 'application/pdf'),
        ]))->assertCreated();

        $schedule = PmSchedule::query()->firstOrFail();

        $this->assertNotNull($schedule->schedule_document_path);
        Storage::disk('public')->assertExists($schedule->schedule_document_path);
    }

    public function test_a_pm_schedule_is_still_created_without_a_document(): void
    {
        Sanctum::actingAs($this->admin);

        $this->postJson('/api/v1/pm-schedules', $this->mileagePmPayload())->assertCreated();

        $this->assertNull(PmSchedule::query()->firstOrFail()->schedule_document_path);
    }

    public function test_the_pm_document_rejects_an_svg(): void
    {
        Sanctum::actingAs($this->admin);

        $this->postJson('/api/v1/pm-schedules', $this->mileagePmPayload([
            'schedule_document' => UploadedFile::fake()->create('sneaky.svg', 10, 'image/svg+xml'),
        ]))->assertStatus(422)->assertJsonValidationErrors('schedule_document');

        $this->assertDatabaseCount('pm_schedules', 0);
    }

    /* ========================== F3 — travel order ============================ */

    private function dispatchPayload(array $overrides = []): array
    {
        $vehicle = Vehicle::factory()->create([
            'agency_id' => $this->agency->id,
            'status' => Vehicle::STATUS_OPERATIONAL,
        ]);
        $driver = User::factory()->driver()->create(['agency_id' => $this->agency->id]);

        return array_merge([
            'vehicle_id' => $vehicle->id,
            'driver_id' => $driver->id,
            'mission_type' => 'Medical Response',
            'location' => 'Brgy. Poblacion',
            'time_out' => now()->format('Y-m-d H:i:s'),
        ], $overrides);
    }

    public function test_a_dispatch_can_be_opened_with_a_travel_order(): void
    {
        Sanctum::actingAs($this->admin);

        $this->postJson('/api/v1/dispatches', $this->dispatchPayload([
            'travel_order' => UploadedFile::fake()->create('travel-order.pdf', 120, 'application/pdf'),
        ]))->assertCreated();

        $dispatch = \App\Models\Dispatch::query()->firstOrFail();

        $this->assertNotNull($dispatch->travel_order_path);
        Storage::disk('public')->assertExists($dispatch->travel_order_path);
    }

    public function test_an_emergency_dispatch_opens_without_a_travel_order(): void
    {
        Sanctum::actingAs($this->admin);

        $this->postJson('/api/v1/dispatches', $this->dispatchPayload())->assertCreated();

        $this->assertNull(\App\Models\Dispatch::query()->firstOrFail()->travel_order_path);
    }

    public function test_the_travel_order_rejects_an_oversized_file(): void
    {
        Sanctum::actingAs($this->admin);

        $this->postJson('/api/v1/dispatches', $this->dispatchPayload([
            'travel_order' => UploadedFile::fake()->create('huge.pdf', 6000, 'application/pdf'),
        ]))->assertStatus(422)->assertJsonValidationErrors('travel_order');

        $this->assertDatabaseCount('dispatches', 0);
    }

    /* --------------------- F3b — optional second driver --------------------- */

    public function test_a_dispatch_can_record_an_optional_second_driver(): void
    {
        Sanctum::actingAs($this->admin);

        $second = User::factory()->driver()->create(['agency_id' => $this->agency->id]);

        $this->postJson('/api/v1/dispatches', $this->dispatchPayload([
            'second_driver_id' => $second->id,
        ]))->assertCreated();

        $this->assertSame($second->id, \App\Models\Dispatch::query()->firstOrFail()->second_driver_id);
    }

    public function test_the_second_driver_is_optional(): void
    {
        Sanctum::actingAs($this->admin);

        $this->postJson('/api/v1/dispatches', $this->dispatchPayload())->assertCreated();

        $this->assertNull(\App\Models\Dispatch::query()->firstOrFail()->second_driver_id);
    }

    public function test_the_second_driver_must_differ_from_the_primary(): void
    {
        Sanctum::actingAs($this->admin);

        $payload = $this->dispatchPayload();
        $payload['second_driver_id'] = $payload['driver_id'];

        $this->postJson('/api/v1/dispatches', $payload)
            ->assertStatus(422)->assertJsonValidationErrors('second_driver_id');
    }

    public function test_a_second_driver_from_another_agency_is_rejected(): void
    {
        Sanctum::actingAs($this->admin);

        $foreign = User::factory()->driver()->create(); // different agency

        $this->postJson('/api/v1/dispatches', $this->dispatchPayload([
            'second_driver_id' => $foreign->id,
        ]))->assertStatus(422)->assertJsonValidationErrors('second_driver_id');
    }

    /**
     * A crew member is out with the vehicle, so they cannot be on another active
     * dispatch — whether they are named as its primary or its second driver.
     */
    public function test_a_second_driver_already_out_is_refused(): void
    {
        Sanctum::actingAs($this->admin);

        // First dispatch: this driver is the PRIMARY.
        $busy = User::factory()->driver()->create(['agency_id' => $this->agency->id]);
        $this->postJson('/api/v1/dispatches', $this->dispatchPayload(['driver_id' => $busy->id]))
            ->assertCreated();

        // Second dispatch tries to use that same person as its SECOND driver.
        $this->postJson('/api/v1/dispatches', $this->dispatchPayload([
            'second_driver_id' => $busy->id,
        ]))->assertStatus(422)->assertJsonValidationErrors('second_driver_id');
    }

    /* ============================== F4 — NC II ============================== */

    public function test_a_driver_is_created_with_an_nc_ii_number_and_expiry(): void
    {
        Sanctum::actingAs($this->admin);

        $this->postJson('/api/v1/drivers', [
            'name' => 'Pedro Santos',
            'email' => 'pedro@example.com',
            'password' => 'secret123',
            'password_confirmation' => 'secret123',
            'nc_ii_number' => 'NCII-DR-0001',
            'nc_ii_expiry_date' => now()->addYear()->toDateString(),
        ])->assertCreated();

        $driver = User::query()->where('email', 'pedro@example.com')->firstOrFail();

        $this->assertSame('NCII-DR-0001', $driver->nc_ii_number);
        $this->assertNotNull($driver->nc_ii_expiry_date);
        $this->assertSame('Valid', $driver->ncIiStatus());
    }

    public function test_nc_ii_is_optional(): void
    {
        Sanctum::actingAs($this->admin);

        $this->postJson('/api/v1/drivers', [
            'name' => 'No Cert',
            'email' => 'nocert@example.com',
            'password' => 'secret123',
            'password_confirmation' => 'secret123',
        ])->assertCreated();

        $driver = User::query()->where('email', 'nocert@example.com')->firstOrFail();

        $this->assertNull($driver->nc_ii_expiry_date);
        $this->assertNull($driver->ncIiStatus());
    }

    public function test_nc_ii_status_flags_an_expired_certificate(): void
    {
        $driver = User::factory()->driver()->create([
            'agency_id' => $this->agency->id,
            'nc_ii_expiry_date' => now()->subDay()->toDateString(),
        ]);

        $this->assertSame('Expired', $driver->ncIiStatus());
    }

    public function test_nc_ii_status_uses_the_agency_warning_window(): void
    {
        $this->agency->update(['license_expiry_warning_days' => 30]);

        $driver = User::factory()->driver()->create([
            'agency_id' => $this->agency->id,
            'nc_ii_expiry_date' => now()->addDays(10)->toDateString(),
        ]);

        $this->assertSame('Expiring Soon', $driver->ncIiStatus());
    }

    /* ========================= F5 — secondary driver ========================= */

    private function vehiclePayload(array $overrides = []): array
    {
        return array_merge([
            'type' => 'Ambulance',
            'plate_number' => 'CHO-0001',
            'make' => 'Toyota',
            'model' => 'HiAce',
            'current_mileage' => 1_000,
        ], $overrides);
    }

    public function test_a_vehicle_can_be_created_with_a_secondary_driver(): void
    {
        Sanctum::actingAs($this->admin);

        $primary = User::factory()->driver()->create(['agency_id' => $this->agency->id]);
        $secondary = User::factory()->driver()->create(['agency_id' => $this->agency->id]);

        $this->postJson('/api/v1/vehicles', $this->vehiclePayload([
            'assigned_driver_id' => $primary->id,
            'secondary_driver_id' => $secondary->id,
        ]))->assertCreated();

        $vehicle = Vehicle::query()->where('plate_number', 'CHO-0001')->firstOrFail();

        $this->assertSame($primary->id, $vehicle->assigned_driver_id);
        $this->assertSame($secondary->id, $vehicle->secondary_driver_id);
    }

    public function test_the_secondary_driver_is_optional(): void
    {
        Sanctum::actingAs($this->admin);

        $primary = User::factory()->driver()->create(['agency_id' => $this->agency->id]);

        $this->postJson('/api/v1/vehicles', $this->vehiclePayload([
            'assigned_driver_id' => $primary->id,
        ]))->assertCreated();

        $this->assertNull(Vehicle::query()->where('plate_number', 'CHO-0001')->firstOrFail()->secondary_driver_id);
    }

    public function test_the_secondary_driver_must_differ_from_the_primary(): void
    {
        Sanctum::actingAs($this->admin);

        $driver = User::factory()->driver()->create(['agency_id' => $this->agency->id]);

        $this->postJson('/api/v1/vehicles', $this->vehiclePayload([
            'assigned_driver_id' => $driver->id,
            'secondary_driver_id' => $driver->id,
        ]))->assertStatus(422)->assertJsonValidationErrors('secondary_driver_id');
    }

    public function test_a_secondary_driver_from_another_agency_is_rejected(): void
    {
        Sanctum::actingAs($this->admin);

        $primary = User::factory()->driver()->create(['agency_id' => $this->agency->id]);
        $foreign = User::factory()->driver()->create(); // different agency

        $this->postJson('/api/v1/vehicles', $this->vehiclePayload([
            'assigned_driver_id' => $primary->id,
            'secondary_driver_id' => $foreign->id,
        ]))->assertStatus(422)->assertJsonValidationErrors('secondary_driver_id');
    }

    public function test_my_vehicle_returns_vehicles_where_the_driver_is_secondary(): void
    {
        $primary = User::factory()->driver()->create(['agency_id' => $this->agency->id]);
        $secondary = User::factory()->driver()->create(['agency_id' => $this->agency->id]);

        $vehicle = Vehicle::factory()->create([
            'agency_id' => $this->agency->id,
            'assigned_driver_id' => $primary->id,
            'secondary_driver_id' => $secondary->id,
            'plate_number' => 'SEC-0001',
        ]);

        // The SECONDARY driver sees the vehicle on their own My Vehicle list (FR-09).
        Sanctum::actingAs($secondary);

        $this->getJson('/api/v1/my-vehicle')
            ->assertOk()
            ->assertJsonFragment(['plate_number' => 'SEC-0001']);
    }

    public function test_my_vehicle_labels_the_drivers_role_on_each_vehicle(): void
    {
        $primary = User::factory()->driver()->create(['agency_id' => $this->agency->id]);
        $secondary = User::factory()->driver()->create(['agency_id' => $this->agency->id]);

        Vehicle::factory()->create([
            'agency_id' => $this->agency->id,
            'assigned_driver_id' => $primary->id,
            'secondary_driver_id' => $secondary->id,
            'plate_number' => 'ROLE-01',
        ]);

        // The primary driver is told they are the primary.
        Sanctum::actingAs($primary);
        $this->getJson('/api/v1/my-vehicle')
            ->assertOk()
            ->assertJsonPath('data.0.my_role', 'primary');

        // The secondary driver is told they are the secondary — no guessing.
        Sanctum::actingAs($secondary);
        $this->getJson('/api/v1/my-vehicle')
            ->assertOk()
            ->assertJsonPath('data.0.my_role', 'secondary');
    }
}
