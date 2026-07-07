<?php

namespace Tests\Feature;

use App\Models\Agency;
use App\Models\User;
use App\Models\Vehicle;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/**
 * An agency may have MORE THAN ONE administrator account (per the
 * interviews — e.g., logistics and operations officers). No code may
 * assume a single admin per agency (CLAUDE.md design decision 6).
 */
class MultiAdminAgencyTest extends TestCase
{
    use RefreshDatabase;

    public function test_two_admins_of_the_same_agency_see_the_same_records(): void
    {
        $agency = Agency::factory()->create();
        $first = User::factory()->admin()->create(['agency_id' => $agency->id]);
        $second = User::factory()->admin()->create(['agency_id' => $agency->id]);
        Vehicle::factory()->count(3)->create(['agency_id' => $agency->id]);
        Vehicle::factory()->create(); // another agency — must stay invisible

        Sanctum::actingAs($first);
        $this->getJson('/api/v1/vehicles')->assertOk()->assertJsonCount(3, 'data');

        $this->app['auth']->forgetGuards();
        Sanctum::actingAs($second);
        $this->getJson('/api/v1/vehicles')->assertOk()->assertJsonCount(3, 'data');
    }

    public function test_second_admin_has_full_admin_rights_in_the_agency(): void
    {
        $agency = Agency::factory()->create();
        User::factory()->admin()->create(['agency_id' => $agency->id]); // first admin
        $second = User::factory()->admin()->create(['agency_id' => $agency->id]);
        $vehicle = Vehicle::factory()->create(['agency_id' => $agency->id]);
        $pending = User::factory()->driver()->pending()->create(['agency_id' => $agency->id]);

        Sanctum::actingAs($second);

        // The second admin can write, not just read: status updates and approvals.
        $this->patchJson("/api/v1/vehicles/{$vehicle->id}/status", ['status' => Vehicle::STATUS_NOT_OPERATIONAL])
            ->assertOk();
        $this->patchJson("/api/v1/drivers/{$pending->id}/approve")
            ->assertOk()->assertJsonPath('data.status', User::STATUS_ACTIVE);
    }

    public function test_second_admin_is_still_blocked_from_other_agencies(): void
    {
        $agency = Agency::factory()->create();
        User::factory()->admin()->create(['agency_id' => $agency->id]);
        $second = User::factory()->admin()->create(['agency_id' => $agency->id]);
        $foreignVehicle = Vehicle::factory()->create(); // another agency

        Sanctum::actingAs($second);

        $this->getJson("/api/v1/vehicles/{$foreignVehicle->id}")->assertStatus(404);
    }
}
