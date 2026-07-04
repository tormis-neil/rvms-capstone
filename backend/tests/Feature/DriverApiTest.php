<?php

namespace Tests\Feature;

use App\Models\Agency;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class DriverApiTest extends TestCase
{
    use RefreshDatabase;

    private function admin(?Agency $agency = null): User
    {
        return User::factory()->admin()->create(['agency_id' => ($agency ?? Agency::factory()->create())->id]);
    }

    public function test_admin_lists_only_own_agency_drivers(): void
    {
        $admin = $this->admin();
        User::factory()->driver()->count(3)->create(['agency_id' => $admin->agency_id]);
        User::factory()->driver()->create(); // another agency

        Sanctum::actingAs($admin);

        $this->getJson('/api/v1/drivers')->assertOk()->assertJsonCount(3, 'data');
    }

    public function test_admin_added_driver_is_active(): void
    {
        $admin = $this->admin();
        Sanctum::actingAs($admin);

        $this->postJson('/api/v1/drivers', [
            'name' => 'New Driver',
            'email' => 'newdriver@example.com',
            'password' => 'secret-password',
            'license_number' => 'LIC-1',
            'license_expiry_date' => '2027-12-31',
        ])->assertCreated()
            ->assertJsonPath('data.status', User::STATUS_ACTIVE)
            ->assertJsonPath('data.role', User::ROLE_DRIVER)
            ->assertJsonPath('data.agency_id', $admin->agency_id);
    }

    public function test_bad_license_payload_is_422(): void
    {
        Sanctum::actingAs($this->admin());

        $this->postJson('/api/v1/drivers', [
            'name' => '',
            'email' => 'not-an-email',
            'license_expiry_date' => 'not-a-date',
        ])->assertStatus(422)
            ->assertJsonValidationErrors(['name', 'email', 'password', 'license_expiry_date']);
    }

    public function test_filter_pending_returns_access_requests(): void
    {
        $admin = $this->admin();
        User::factory()->driver()->pending()->count(2)->create(['agency_id' => $admin->agency_id]);
        User::factory()->driver()->create(['agency_id' => $admin->agency_id]);

        Sanctum::actingAs($admin);

        $this->getJson('/api/v1/drivers?status=pending')->assertOk()->assertJsonCount(2, 'data');
    }

    public function test_approve_and_reject_flip_pending_status(): void
    {
        $admin = $this->admin();
        $pending = User::factory()->driver()->pending()->create(['agency_id' => $admin->agency_id]);
        $other = User::factory()->driver()->pending()->create(['agency_id' => $admin->agency_id]);

        Sanctum::actingAs($admin);

        $this->patchJson("/api/v1/drivers/{$pending->id}/approve")
            ->assertOk()->assertJsonPath('data.status', User::STATUS_ACTIVE);
        $this->patchJson("/api/v1/drivers/{$other->id}/reject")
            ->assertOk()->assertJsonPath('data.status', User::STATUS_REJECTED);
    }

    public function test_unauthenticated_is_401_and_driver_is_403(): void
    {
        $this->getJson('/api/v1/drivers')->assertStatus(401);

        Sanctum::actingAs(User::factory()->driver()->create());
        $this->getJson('/api/v1/drivers')->assertStatus(403);
    }

    public function test_admin_cannot_read_another_agencys_driver(): void
    {
        $foreign = User::factory()->driver()->create();
        Sanctum::actingAs($this->admin());

        $this->getJson("/api/v1/drivers/{$foreign->id}")->assertStatus(404);
    }

    public function test_admin_cannot_approve_another_agencys_pending_driver(): void
    {
        $foreign = User::factory()->driver()->pending()->create();
        Sanctum::actingAs($this->admin());

        $this->patchJson("/api/v1/drivers/{$foreign->id}/approve")->assertStatus(404);
        $this->assertSame(User::STATUS_PENDING, $foreign->refresh()->status);
    }

    public function test_admin_route_rejects_targeting_an_admin_as_driver(): void
    {
        $admin = $this->admin();
        $otherAdmin = User::factory()->admin()->create(['agency_id' => $admin->agency_id]);
        Sanctum::actingAs($admin);

        $this->getJson("/api/v1/drivers/{$otherAdmin->id}")->assertStatus(404);
    }
}
