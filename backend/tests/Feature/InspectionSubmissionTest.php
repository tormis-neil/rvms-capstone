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
 * R3 — POST /api/v1/inspections (FR-09): driver submits a complete
 * BLOWBAGETS checklist; remarks required on flagged items; the checklist
 * must be complete and match the driver's agency; the vehicle must belong
 * to the driver's agency; admins cannot submit.
 */
class InspectionSubmissionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(InspectionChecklistSeeder::class);
    }

    /**
     * Build a full OK payload for the given agency code, optionally flagging one item.
     */
    private function fullPayload(string $code, int $vehicleId, ?string $flagItem = null, bool $withRemarks = true): array
    {
        $items = InspectionChecklistItem::forAgencyCode($code)->get()->map(function ($item) use ($flagItem, $withRemarks) {
            if ($flagItem && $item->name === $flagItem) {
                return array_filter([
                    'checklist_item_id' => $item->id,
                    'status' => 'Has Issue',
                    'remarks' => $withRemarks ? 'Problem found' : null,
                ], fn ($v) => $v !== null);
            }

            return ['checklist_item_id' => $item->id, 'status' => 'OK'];
        })->all();

        return ['vehicle_id' => $vehicleId, 'items' => $items];
    }

    private function bfpDriverWithVehicle(): array
    {
        $agency = Agency::factory()->create(['code' => 'BFP']);
        $driver = User::factory()->driver()->create(['agency_id' => $agency->id]);
        $vehicle = Vehicle::factory()->create(['agency_id' => $agency->id]);

        return [$driver, $vehicle];
    }

    public function test_driver_submits_a_complete_all_ok_inspection(): void
    {
        [$driver, $vehicle] = $this->bfpDriverWithVehicle();
        Sanctum::actingAs($driver);

        $this->postJson('/api/v1/inspections', $this->fullPayload('BFP', $vehicle->id))
            ->assertCreated()
            ->assertJsonPath('data.result', 'All OK')
            ->assertJsonPath('data.review_status', Inspection::STATUS_PENDING)
            ->assertJsonCount(14, 'data.items');

        $this->assertDatabaseHas('inspections', [
            'vehicle_id' => $vehicle->id,
            'driver_id' => $driver->id,
            'agency_id' => $driver->agency_id,
        ]);
    }

    public function test_flagged_item_requires_remarks(): void
    {
        [$driver, $vehicle] = $this->bfpDriverWithVehicle();
        Sanctum::actingAs($driver);

        $this->postJson('/api/v1/inspections', $this->fullPayload('BFP', $vehicle->id, 'Brakes', withRemarks: false))
            ->assertStatus(422)
            ->assertJsonValidationErrors(['items.4.remarks']);
    }

    public function test_flagged_item_with_remarks_is_accepted_and_result_is_has_issue(): void
    {
        [$driver, $vehicle] = $this->bfpDriverWithVehicle();
        Sanctum::actingAs($driver);

        $this->postJson('/api/v1/inspections', $this->fullPayload('BFP', $vehicle->id, 'Brakes'))
            ->assertCreated()
            ->assertJsonPath('data.result', 'Has Issue');
    }

    public function test_incomplete_checklist_is_rejected(): void
    {
        [$driver, $vehicle] = $this->bfpDriverWithVehicle();
        Sanctum::actingAs($driver);

        $this->postJson('/api/v1/inspections', [
            'vehicle_id' => $vehicle->id,
            'items' => [['checklist_item_id' => InspectionChecklistItem::first()->id, 'status' => 'OK']],
        ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['items']);
    }

    public function test_non_bfp_driver_submitting_bfp_only_items_is_rejected(): void
    {
        $agency = Agency::factory()->create(['code' => 'PNP']);
        $driver = User::factory()->driver()->create(['agency_id' => $agency->id]);
        $vehicle = Vehicle::factory()->create(['agency_id' => $agency->id]);

        Sanctum::actingAs($driver);

        // A full 14-item BFP payload includes the 2 BFP-only items PNP must not send.
        $this->postJson('/api/v1/inspections', $this->fullPayload('BFP', $vehicle->id))
            ->assertStatus(422)
            ->assertJsonValidationErrors(['items']);
    }

    public function test_cannot_submit_for_another_agencys_vehicle(): void
    {
        [$driver] = $this->bfpDriverWithVehicle();
        $foreignVehicle = Vehicle::factory()->create(); // other agency

        Sanctum::actingAs($driver);

        $this->postJson('/api/v1/inspections', $this->fullPayload('BFP', $foreignVehicle->id))
            ->assertStatus(422)
            ->assertJsonValidationErrors(['vehicle_id']);
    }

    public function test_admin_cannot_submit_an_inspection(): void
    {
        Sanctum::actingAs(User::factory()->admin()->create());

        $this->postJson('/api/v1/inspections', ['vehicle_id' => 1, 'items' => []])
            ->assertForbidden();
    }

    public function test_unauthenticated_submission_gets_401(): void
    {
        $this->postJson('/api/v1/inspections', [])->assertUnauthorized();
    }
}
