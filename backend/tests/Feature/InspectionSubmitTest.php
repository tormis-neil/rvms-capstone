<?php

namespace Tests\Feature;

use App\Models\Agency;
use App\Models\InspectionChecklistItem;
use App\Models\User;
use App\Models\Vehicle;
use Database\Seeders\InspectionChecklistItemSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class InspectionSubmitTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(InspectionChecklistItemSeeder::class);
    }

    private function driverWithVehicle(string $agencyCode = 'PNPX'): array
    {
        $agency = Agency::factory()->create(['code' => $agencyCode]);
        $driver = User::factory()->driver()->create(['agency_id' => $agency->id]);
        $vehicle = Vehicle::factory()->create(['agency_id' => $agency->id]);

        return [$driver, $vehicle, $agency];
    }

    /** A full all-OK payload matching the driver's agency checklist. */
    private function payloadFor(User $driver, Vehicle $vehicle): array
    {
        $items = InspectionChecklistItem::forAgency($driver->agency)->get()
            ->map(fn ($item) => ['checklist_item_id' => $item->id, 'status' => 'OK'])
            ->all();

        return [
            'vehicle_id' => $vehicle->id,
            'inspection_date' => now()->toDateString(),
            'items' => $items,
        ];
    }

    public function test_bfp_checklist_has_14_items_and_others_get_12(): void
    {
        [$bfpDriver] = $this->driverWithVehicle('BFP');
        Sanctum::actingAs($bfpDriver);
        $this->getJson('/api/v1/inspections/checklist')
            ->assertOk()->assertJsonPath('count', 14);

        [$otherDriver] = $this->driverWithVehicle('CHOX');
        $this->app['auth']->forgetGuards(); // drop the cached BFP driver
        Sanctum::actingAs($otherDriver);
        $this->getJson('/api/v1/inspections/checklist')
            ->assertOk()->assertJsonPath('count', 12);
    }

    public function test_valid_submission_returns_201_with_stored_items(): void
    {
        [$driver, $vehicle] = $this->driverWithVehicle();
        Sanctum::actingAs($driver);

        $this->postJson('/api/v1/inspections', $this->payloadFor($driver, $vehicle))
            ->assertCreated()
            ->assertJsonPath('data.review_status', 'Pending')
            ->assertJsonPath('data.driver_id', $driver->id)
            ->assertJsonCount(12, 'data.items');

        $this->assertDatabaseCount('inspection_items', 12);
    }

    public function test_has_issue_without_remarks_returns_422(): void
    {
        [$driver, $vehicle] = $this->driverWithVehicle();
        Sanctum::actingAs($driver);

        $payload = $this->payloadFor($driver, $vehicle);
        $payload['items'][0]['status'] = 'Has Issue'; // no remarks

        $this->postJson('/api/v1/inspections', $payload)
            ->assertStatus(422)
            ->assertJsonValidationErrors(['items.0.remarks']);
    }

    public function test_has_issue_with_remarks_is_accepted(): void
    {
        [$driver, $vehicle] = $this->driverWithVehicle();
        Sanctum::actingAs($driver);

        $payload = $this->payloadFor($driver, $vehicle);
        $payload['items'][0]['status'] = 'Has Issue';
        $payload['items'][0]['remarks'] = 'Battery terminals corroded';

        $this->postJson('/api/v1/inspections', $payload)
            ->assertCreated()
            ->assertJsonPath('data.issue_count', 1);
    }

    public function test_incomplete_checklist_is_rejected(): void
    {
        [$driver, $vehicle] = $this->driverWithVehicle();
        Sanctum::actingAs($driver);

        $payload = $this->payloadFor($driver, $vehicle);
        array_pop($payload['items']); // drop one required item

        $this->postJson('/api/v1/inspections', $payload)
            ->assertStatus(422)
            ->assertJsonValidationErrors(['items']);
    }

    public function test_non_bfp_driver_cannot_submit_bfp_only_items(): void
    {
        [$driver, $vehicle] = $this->driverWithVehicle('CHOX');
        Sanctum::actingAs($driver);

        $payload = $this->payloadFor($driver, $vehicle);
        $bfpOnly = InspectionChecklistItem::where('is_bfp_only', true)->first();
        $payload['items'][] = ['checklist_item_id' => $bfpOnly->id, 'status' => 'OK'];

        $this->postJson('/api/v1/inspections', $payload)
            ->assertStatus(422)
            ->assertJsonValidationErrors(['items']);
    }

    public function test_vehicle_must_belong_to_drivers_agency(): void
    {
        [$driver] = $this->driverWithVehicle();
        $foreignVehicle = Vehicle::factory()->create(); // another agency
        Sanctum::actingAs($driver);

        $payload = $this->payloadFor($driver, $foreignVehicle);

        $this->postJson('/api/v1/inspections', $payload)
            ->assertStatus(422)
            ->assertJsonValidationErrors(['vehicle_id']);
    }

    public function test_admin_token_cannot_submit(): void
    {
        [$driver, $vehicle, $agency] = $this->driverWithVehicle();
        $admin = User::factory()->admin()->create(['agency_id' => $agency->id]);
        Sanctum::actingAs($admin);

        $this->postJson('/api/v1/inspections', $this->payloadFor($driver, $vehicle))
            ->assertStatus(403);
    }

    public function test_unauthenticated_is_401(): void
    {
        $this->postJson('/api/v1/inspections', [])->assertStatus(401);
        $this->getJson('/api/v1/inspections/checklist')->assertStatus(401);
    }
}
