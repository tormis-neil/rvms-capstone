<?php

namespace Tests\Feature;

use App\Models\Agency;
use App\Models\User;
use Database\Seeders\InspectionChecklistSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/**
 * R3 — GET /api/v1/inspections/checklist (FR-09): BFP drivers get 14 items,
 * every other agency's drivers get the 12 standard items.
 */
class InspectionChecklistTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(InspectionChecklistSeeder::class);
    }

    private function driverOf(string $code): User
    {
        $agency = Agency::factory()->create(['code' => $code]);

        return User::factory()->driver()->create(['agency_id' => $agency->id]);
    }

    public function test_bfp_driver_gets_all_14_items_including_the_two_bfp_only(): void
    {
        Sanctum::actingAs($this->driverOf('BFP'));

        $response = $this->getJson('/api/v1/inspections/checklist')
            ->assertOk()
            ->assertJsonCount(14, 'data');

        $names = collect($response->json('data'))->pluck('name');
        $this->assertTrue($names->contains('Hydraulic System'));
        $this->assertTrue($names->contains('Fire Pump'));
    }

    public function test_non_bfp_driver_gets_the_12_standard_items_only(): void
    {
        Sanctum::actingAs($this->driverOf('PNP'));

        $response = $this->getJson('/api/v1/inspections/checklist')
            ->assertOk()
            ->assertJsonCount(12, 'data');

        $names = collect($response->json('data'))->pluck('name');
        $this->assertFalse($names->contains('Hydraulic System'));
        $this->assertFalse($names->contains('Fire Pump'));
    }

    public function test_admin_cannot_fetch_the_driver_checklist(): void
    {
        Sanctum::actingAs(User::factory()->admin()->create());

        $this->getJson('/api/v1/inspections/checklist')->assertForbidden();
    }

    public function test_unauthenticated_request_gets_401(): void
    {
        $this->getJson('/api/v1/inspections/checklist')->assertUnauthorized();
    }
}
