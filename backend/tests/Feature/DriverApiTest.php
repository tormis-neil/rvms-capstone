<?php

namespace Tests\Feature;

use App\Models\Agency;
use App\Models\User;
use App\Models\Vehicle;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/**
 * R2 — /api/v1/drivers (FR-03, FR-06, FR-08): shapes, validation, role
 * gating, agency isolation, approve/reject, license renewal, multi-vehicle.
 */
class DriverApiTest extends TestCase
{
    use RefreshDatabase;

    private Agency $agency;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->agency = Agency::factory()->create(['license_expiry_warning_days' => 30]);
        $this->admin = User::factory()->admin()->create(['agency_id' => $this->agency->id]);
    }

    private function actingAsAdmin(): void
    {
        Sanctum::actingAs($this->admin);
    }

    private function validPayload(array $overrides = []): array
    {
        return $overrides + [
            'name' => 'Juan Dela Cruz',
            'email' => 'juan.delacruz@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'license_number' => 'N01-12-345678',
            'license_expiry_date' => now()->addYear()->toDateString(),
        ];
    }

    public function test_index_returns_only_own_agency_active_drivers(): void
    {
        User::factory()->driver()->create(['agency_id' => $this->agency->id]);
        User::factory()->driver()->pending()->create(['agency_id' => $this->agency->id]);
        User::factory()->driver()->create(); // other agency

        $this->actingAsAdmin();

        $this->getJson('/api/v1/drivers')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonStructure(['data' => [['id', 'name', 'email', 'status', 'license_status']]]);
    }

    public function test_index_can_filter_by_status_for_access_requests(): void
    {
        User::factory()->driver()->pending()->create(['agency_id' => $this->agency->id]);
        User::factory()->driver()->create(['agency_id' => $this->agency->id]);

        $this->actingAsAdmin();

        $this->getJson('/api/v1/drivers?status=pending')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.status', 'pending');
    }

    public function test_store_creates_active_driver_stamped_with_admin_agency(): void
    {
        $this->actingAsAdmin();

        $response = $this->postJson('/api/v1/drivers', $this->validPayload())
            ->assertCreated()
            ->assertJsonPath('data.status', User::STATUS_ACTIVE)
            ->assertJsonPath('data.email', 'juan.delacruz@example.com');

        $driver = User::withoutGlobalScopes()->findOrFail($response->json('data.id'));
        $this->assertSame($this->agency->id, $driver->agency_id);
        $this->assertSame(User::ROLE_DRIVER, $driver->role);
    }

    public function test_store_assigns_the_optional_vehicle(): void
    {
        $vehicle = Vehicle::factory()->create(['agency_id' => $this->agency->id]);

        $this->actingAsAdmin();

        $response = $this->postJson('/api/v1/drivers', $this->validPayload(['assigned_vehicle_id' => $vehicle->id]))
            ->assertCreated();

        $this->assertSame($response->json('data.id'), $vehicle->fresh()->assigned_driver_id);
    }

    public function test_store_cannot_steal_a_vehicle_already_assigned_to_another_driver(): void
    {
        $otherDriver = User::factory()->driver()->create(['agency_id' => $this->agency->id]);
        $vehicle = Vehicle::factory()->create(['agency_id' => $this->agency->id, 'assigned_driver_id' => $otherDriver->id]);

        $this->actingAsAdmin();

        $this->postJson('/api/v1/drivers', $this->validPayload(['assigned_vehicle_id' => $vehicle->id]))
            ->assertStatus(422)
            ->assertJsonValidationErrors(['assigned_vehicle_id']);
    }

    public function test_store_rejects_missing_required_fields(): void
    {
        $this->actingAsAdmin();

        $this->postJson('/api/v1/drivers', [])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['name', 'email', 'password']);
    }

    public function test_email_must_be_unique(): void
    {
        User::factory()->create(['email' => 'taken@example.com']);

        $this->actingAsAdmin();

        $this->postJson('/api/v1/drivers', $this->validPayload(['email' => 'taken@example.com']))
            ->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
    }

    public function test_a_driver_may_be_assigned_more_than_one_vehicle(): void
    {
        $driver = User::factory()->driver()->create(['agency_id' => $this->agency->id]);
        $first = Vehicle::factory()->create(['agency_id' => $this->agency->id]);
        $second = Vehicle::factory()->create(['agency_id' => $this->agency->id]);

        $this->actingAsAdmin();

        $this->putJson("/api/v1/drivers/{$driver->id}", [
            'name' => $driver->name, 'email' => $driver->email, 'assigned_vehicle_id' => $first->id,
        ])->assertOk();
        $this->putJson("/api/v1/drivers/{$driver->id}", [
            'name' => $driver->name, 'email' => $driver->email, 'assigned_vehicle_id' => $second->id,
        ])->assertOk()->assertJsonCount(2, 'data.vehicles');

        $this->assertSame($driver->id, $first->fresh()->assigned_driver_id);
        $this->assertSame($driver->id, $second->fresh()->assigned_driver_id);
    }

    public function test_update_edit_vehicle_select_can_never_steal_another_drivers_vehicle(): void
    {
        $driver = User::factory()->driver()->create(['agency_id' => $this->agency->id]);
        $otherDriver = User::factory()->driver()->create(['agency_id' => $this->agency->id]);
        $stolenVehicle = Vehicle::factory()->create(['agency_id' => $this->agency->id, 'assigned_driver_id' => $otherDriver->id]);

        $this->actingAsAdmin();

        $this->putJson("/api/v1/drivers/{$driver->id}", [
            'name' => $driver->name, 'email' => $driver->email, 'assigned_vehicle_id' => $stolenVehicle->id,
        ])->assertStatus(422)->assertJsonValidationErrors(['assigned_vehicle_id']);

        $this->assertSame($otherDriver->id, $stolenVehicle->fresh()->assigned_driver_id);
    }

    public function test_update_without_password_keeps_the_existing_password(): void
    {
        $driver = User::factory()->driver()->create(['agency_id' => $this->agency->id, 'password' => 'original-password']);
        $originalHash = $driver->password;

        $this->actingAsAdmin();

        $this->putJson("/api/v1/drivers/{$driver->id}", ['name' => 'New Name', 'email' => $driver->email])
            ->assertOk();

        $this->assertSame($originalHash, $driver->fresh()->password);
    }

    public function test_cross_agency_driver_returns_404(): void
    {
        $foreign = User::factory()->driver()->create();

        $this->actingAsAdmin();

        $this->getJson("/api/v1/drivers/{$foreign->id}")->assertNotFound();
        $this->putJson("/api/v1/drivers/{$foreign->id}", $this->validPayload())->assertNotFound();
        $this->patchJson("/api/v1/drivers/{$foreign->id}/approve")->assertNotFound();
        $this->patchJson("/api/v1/drivers/{$foreign->id}/reject")->assertNotFound();
    }

    public function test_cannot_fetch_an_admin_through_the_driver_endpoint(): void
    {
        $otherAdmin = User::factory()->admin()->create(['agency_id' => $this->agency->id]);

        $this->actingAsAdmin();

        $this->getJson("/api/v1/drivers/{$otherAdmin->id}")->assertNotFound();
    }

    public function test_approve_activates_a_pending_driver(): void
    {
        $pending = User::factory()->driver()->pending()->create(['agency_id' => $this->agency->id]);

        $this->actingAsAdmin();

        $this->patchJson("/api/v1/drivers/{$pending->id}/approve")
            ->assertOk()
            ->assertJsonPath('data.status', User::STATUS_ACTIVE);
    }

    public function test_reject_marks_a_pending_driver_rejected(): void
    {
        $pending = User::factory()->driver()->pending()->create(['agency_id' => $this->agency->id]);

        $this->actingAsAdmin();

        $this->patchJson("/api/v1/drivers/{$pending->id}/reject")
            ->assertOk()
            ->assertJsonPath('data.status', User::STATUS_REJECTED);
    }

    public function test_license_renewal_updates_expiry_date(): void
    {
        $driver = User::factory()->driver()->create([
            'agency_id' => $this->agency->id,
            'license_expiry_date' => now()->subDay(),
        ]);

        $this->actingAsAdmin();

        $newDate = now()->addYears(5)->toDateString();

        $this->patchJson("/api/v1/drivers/{$driver->id}/license", ['license_expiry_date' => $newDate])
            ->assertOk()
            ->assertJsonPath('data.license_expiry_date', $newDate)
            ->assertJsonPath('data.license_status', 'Valid');
    }

    public function test_license_renewal_requires_a_date(): void
    {
        $driver = User::factory()->driver()->create(['agency_id' => $this->agency->id]);

        $this->actingAsAdmin();

        $this->patchJson("/api/v1/drivers/{$driver->id}/license", [])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['license_expiry_date']);
    }

    public function test_driver_token_is_refused_on_admin_driver_routes(): void
    {
        $driver = User::factory()->driver()->create(['agency_id' => $this->agency->id]);

        Sanctum::actingAs($driver);

        $this->getJson('/api/v1/drivers')->assertForbidden();
        $this->postJson('/api/v1/drivers', $this->validPayload())->assertForbidden();
    }

    public function test_unauthenticated_requests_get_401(): void
    {
        $this->getJson('/api/v1/drivers')->assertUnauthorized();
    }
}
