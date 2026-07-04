<?php

namespace Tests\Feature;

use App\Models\Agency;
use App\Models\Inspection;
use App\Models\User;
use App\Models\Vehicle;
use Database\Seeders\InspectionChecklistItemSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class InspectionMonitoringTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(InspectionChecklistItemSeeder::class);
    }

    public function test_admin_lists_only_own_agency_inspections(): void
    {
        $agency = Agency::factory()->create();
        $admin = User::factory()->admin()->create(['agency_id' => $agency->id]);
        Inspection::factory()->count(2)->create(['agency_id' => $agency->id]);
        Inspection::factory()->create(); // another agency

        Sanctum::actingAs($admin);

        $this->getJson('/api/v1/inspections')->assertOk()->assertJsonCount(2, 'data');
    }

    public function test_filters_by_vehicle_driver_and_date(): void
    {
        $agency = Agency::factory()->create();
        $admin = User::factory()->admin()->create(['agency_id' => $agency->id]);
        $target = Inspection::factory()->create([
            'agency_id' => $agency->id,
            'inspection_date' => '2026-07-01',
        ]);
        Inspection::factory()->create([
            'agency_id' => $agency->id,
            'inspection_date' => '2026-06-01',
        ]);

        Sanctum::actingAs($admin);

        $this->getJson('/api/v1/inspections?date=2026-07-01')
            ->assertOk()->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $target->id);

        $this->getJson("/api/v1/inspections?vehicle_id={$target->vehicle_id}")
            ->assertOk()->assertJsonCount(1, 'data');

        $this->getJson("/api/v1/inspections?driver_id={$target->driver_id}")
            ->assertOk()->assertJsonCount(1, 'data');
    }

    public function test_bad_filter_input_is_422(): void
    {
        $admin = User::factory()->admin()->create();
        Sanctum::actingAs($admin);

        $this->getJson('/api/v1/inspections?date=not-a-date')->assertStatus(422);
        $this->getJson('/api/v1/inspections?review_status=Bogus')->assertStatus(422);
    }

    public function test_admin_reviews_an_inspection_with_optional_status_change(): void
    {
        $agency = Agency::factory()->create();
        $admin = User::factory()->admin()->create(['agency_id' => $agency->id]);
        $inspection = Inspection::factory()->create(['agency_id' => $agency->id]);

        Sanctum::actingAs($admin);

        $this->patchJson("/api/v1/inspections/{$inspection->id}/review", [
            'vehicle_status' => Vehicle::STATUS_NOT_OPERATIONAL,
        ])->assertOk()
            ->assertJsonPath('data.review_status', Inspection::REVIEW_REVIEWED)
            ->assertJsonPath('data.reviewed_by', $admin->id);

        $this->assertSame(
            Vehicle::STATUS_NOT_OPERATIONAL,
            $inspection->vehicle->refresh()->status,
        );
    }

    public function test_review_rejects_invalid_vehicle_status(): void
    {
        $agency = Agency::factory()->create();
        $admin = User::factory()->admin()->create(['agency_id' => $agency->id]);
        $inspection = Inspection::factory()->create(['agency_id' => $agency->id]);

        Sanctum::actingAs($admin);

        $this->patchJson("/api/v1/inspections/{$inspection->id}/review", [
            'vehicle_status' => 'Broken',
        ])->assertStatus(422);
    }

    public function test_driver_token_is_403_on_monitoring_routes(): void
    {
        $driver = User::factory()->driver()->create();
        Sanctum::actingAs($driver);

        $this->getJson('/api/v1/inspections')->assertStatus(403);
        $this->getJson('/api/v1/inspections/frequent-issues')->assertStatus(403);
    }

    public function test_cross_agency_read_and_review_are_404(): void
    {
        $foreign = Inspection::factory()->create();
        $admin = User::factory()->admin()->create();
        Sanctum::actingAs($admin);

        $this->getJson("/api/v1/inspections/{$foreign->id}")->assertStatus(404);
        $this->patchJson("/api/v1/inspections/{$foreign->id}/review")->assertStatus(404);
        $this->assertSame(Inspection::REVIEW_PENDING, $foreign->refresh()->review_status);
    }

    public function test_unauthenticated_is_401(): void
    {
        $this->getJson('/api/v1/inspections')->assertStatus(401);
    }
}
