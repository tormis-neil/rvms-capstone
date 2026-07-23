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
 * R3 (driver history) — GET /api/v1/inspections and /inspections/{id} scoped
 * for a driver: they see only their OWN submissions (FR-09), never another
 * driver's, while admins keep their agency-wide monitoring view (FR-10).
 */
class InspectionDriverHistoryTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(InspectionChecklistSeeder::class);
    }

    private function makeInspection(Agency $agency, User $driver): Inspection
    {
        $vehicle = Vehicle::factory()->create(['agency_id' => $agency->id]);
        $inspection = Inspection::factory()->create([
            'agency_id' => $agency->id,
            'vehicle_id' => $vehicle->id,
            'driver_id' => $driver->id,
        ]);

        foreach (InspectionChecklistItem::forAgencyCode($agency->code)->get() as $item) {
            $inspection->items()->create(['checklist_item_id' => $item->id, 'status' => 'OK']);
        }

        return $inspection;
    }

    public function test_driver_history_lists_only_their_own_inspections(): void
    {
        $agency = Agency::factory()->create(['code' => 'BFP']);
        $me = User::factory()->driver()->create(['agency_id' => $agency->id]);
        $coworker = User::factory()->driver()->create(['agency_id' => $agency->id]);

        $mine = $this->makeInspection($agency, $me);
        $theirs = $this->makeInspection($agency, $coworker);

        Sanctum::actingAs($me);

        $response = $this->getJson('/api/v1/inspections');

        $response->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $mine->id);

        // The co-worker's inspection is not in the driver's own history.
        $ids = collect($response->json('data'))->pluck('id');
        $this->assertFalse($ids->contains($theirs->id));
    }

    public function test_driver_can_open_their_own_inspection_detail(): void
    {
        $agency = Agency::factory()->create(['code' => 'BFP']);
        $me = User::factory()->driver()->create(['agency_id' => $agency->id]);
        $mine = $this->makeInspection($agency, $me);

        Sanctum::actingAs($me);

        $this->getJson("/api/v1/inspections/{$mine->id}")
            ->assertOk()
            ->assertJsonPath('data.id', $mine->id)
            ->assertJsonCount(14, 'data.items'); // BFP = 12 + 2
    }

    public function test_driver_cannot_open_another_drivers_inspection(): void
    {
        $agency = Agency::factory()->create(['code' => 'BFP']);
        $me = User::factory()->driver()->create(['agency_id' => $agency->id]);
        $coworker = User::factory()->driver()->create(['agency_id' => $agency->id]);
        $theirs = $this->makeInspection($agency, $coworker);

        Sanctum::actingAs($me);

        $this->getJson("/api/v1/inspections/{$theirs->id}")->assertNotFound();
    }

    public function test_admin_still_sees_the_whole_agency_history(): void
    {
        $agency = Agency::factory()->create(['code' => 'BFP']);
        $admin = User::factory()->admin()->create(['agency_id' => $agency->id]);
        $driverA = User::factory()->driver()->create(['agency_id' => $agency->id]);
        $driverB = User::factory()->driver()->create(['agency_id' => $agency->id]);

        $this->makeInspection($agency, $driverA);
        $this->makeInspection($agency, $driverB);

        Sanctum::actingAs($admin);

        $this->getJson('/api/v1/inspections')
            ->assertOk()
            ->assertJsonCount(2, 'data');
    }
}
