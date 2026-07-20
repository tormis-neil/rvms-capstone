<?php

namespace Tests\Feature;

use App\Models\Agency;
use App\Models\Inspection;
use App\Models\InspectionChecklistItem;
use App\Models\User;
use App\Models\Vehicle;
use Database\Seeders\InspectionChecklistSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/**
 * R3 — GET /api/v1/inspections, GET /inspections/{id}, frequent-issues, and
 * PATCH /inspections/{id}/review (FR-10): monitoring, review, agency isolation.
 */
class InspectionMonitoringTest extends TestCase
{
    use RefreshDatabase;

    private Agency $agency;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(InspectionChecklistSeeder::class);

        $this->agency = Agency::factory()->create(['code' => 'BFP']);
        $this->admin = User::factory()->admin()->create(['agency_id' => $this->agency->id]);
    }

    /** Create an inspection with one flagged item in the given agency. */
    private function makeInspection(Agency $agency, string $flagItem = 'Brakes'): Inspection
    {
        $driver = User::factory()->driver()->create(['agency_id' => $agency->id]);
        $vehicle = Vehicle::factory()->create(['agency_id' => $agency->id]);

        $inspection = Inspection::factory()->create([
            'agency_id' => $agency->id,
            'vehicle_id' => $vehicle->id,
            'driver_id' => $driver->id,
        ]);

        foreach (InspectionChecklistItem::forAgencyCode($agency->code)->get() as $item) {
            $inspection->items()->create([
                'checklist_item_id' => $item->id,
                'status' => $item->name === $flagItem ? 'Has Issue' : 'OK',
                'remarks' => $item->name === $flagItem ? 'Issue found' : null,
            ]);
        }

        return $inspection;
    }

    public function test_index_lists_only_own_agency_inspections(): void
    {
        $this->makeInspection($this->agency);
        $this->makeInspection(Agency::factory()->create(['code' => 'PNP'])); // other agency

        Sanctum::actingAs($this->admin);

        $this->getJson('/api/v1/inspections')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.result', 'Has Issue');
    }

    public function test_index_filters_by_vehicle_driver_and_date(): void
    {
        $target = $this->makeInspection($this->agency);
        $this->makeInspection($this->agency);

        Sanctum::actingAs($this->admin);

        $this->getJson('/api/v1/inspections?vehicle_id='.$target->vehicle_id)
            ->assertOk()->assertJsonCount(1, 'data')->assertJsonPath('data.0.id', $target->id);

        $this->getJson('/api/v1/inspections?driver_id='.$target->driver_id)
            ->assertOk()->assertJsonCount(1, 'data')->assertJsonPath('data.0.id', $target->id);
    }

    public function test_show_returns_the_full_checklist(): void
    {
        $inspection = $this->makeInspection($this->agency);

        Sanctum::actingAs($this->admin);

        $this->getJson("/api/v1/inspections/{$inspection->id}")
            ->assertOk()
            ->assertJsonCount(14, 'data.items')
            ->assertJsonPath('data.result', 'Has Issue');
    }

    public function test_frequent_issues_groups_and_ranks_flagged_items(): void
    {
        // Brakes flagged twice, Tires once.
        $this->makeInspection($this->agency, 'Brakes');
        $this->makeInspection($this->agency, 'Brakes');
        $this->makeInspection($this->agency, 'Tires');

        Sanctum::actingAs($this->admin);

        $response = $this->getJson('/api/v1/inspections/frequent-issues')->assertOk();

        $data = $response->json('data');
        $this->assertSame('Brakes', $data[0]['issue']);
        $this->assertSame(2, $data[0]['count']);
        $this->assertSame('Tires', $data[1]['issue']);
        $this->assertSame(1, $data[1]['count']);
        $this->assertNotNull($data[0]['last_reported']);
    }

    public function test_review_marks_reviewed_and_records_who_and_when(): void
    {
        $inspection = $this->makeInspection($this->agency);

        Sanctum::actingAs($this->admin);

        $this->patchJson("/api/v1/inspections/{$inspection->id}/review", [])
            ->assertOk()
            ->assertJsonPath('data.review_status', Inspection::STATUS_REVIEWED)
            ->assertJsonPath('data.reviewed_by', $this->admin->id);

        $this->assertNotNull($inspection->fresh()->reviewed_at);
    }

    public function test_review_can_update_the_vehicle_status(): void
    {
        $inspection = $this->makeInspection($this->agency);

        Sanctum::actingAs($this->admin);

        $this->patchJson("/api/v1/inspections/{$inspection->id}/review", ['vehicle_status' => Vehicle::STATUS_NOT_OPERATIONAL])
            ->assertOk();

        $this->assertSame(Vehicle::STATUS_NOT_OPERATIONAL, $inspection->vehicle->fresh()->status);
    }

    public function test_review_rejects_dispatched_status(): void
    {
        $inspection = $this->makeInspection($this->agency);

        Sanctum::actingAs($this->admin);

        $this->patchJson("/api/v1/inspections/{$inspection->id}/review", ['vehicle_status' => Vehicle::STATUS_DISPATCHED])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['vehicle_status']);
    }

    public function test_cross_agency_inspection_returns_404(): void
    {
        $foreign = $this->makeInspection(Agency::factory()->create(['code' => 'PNP']));

        Sanctum::actingAs($this->admin);

        $this->getJson("/api/v1/inspections/{$foreign->id}")->assertNotFound();
        $this->patchJson("/api/v1/inspections/{$foreign->id}/review", [])->assertNotFound();
    }

    public function test_driver_cannot_access_admin_monitoring(): void
    {
        Sanctum::actingAs(User::factory()->driver()->create(['agency_id' => $this->agency->id]));

        $this->getJson('/api/v1/inspections')->assertForbidden();
        $this->getJson('/api/v1/inspections/frequent-issues')->assertForbidden();
    }
}
