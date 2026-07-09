<?php

namespace Tests\Feature;

use App\Models\Agency;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/**
 * R2 — GET /api/v1/licenses/monitoring (FR-08): Valid / Expiring Soon /
 * Expired counts against the agency's configurable threshold, at the
 * exact boundary.
 */
class LicenseMonitoringTest extends TestCase
{
    use RefreshDatabase;

    public function test_counts_use_the_agencys_configurable_threshold(): void
    {
        $agency = Agency::factory()->create(['license_expiry_warning_days' => 30]);
        $admin = User::factory()->admin()->create(['agency_id' => $agency->id]);

        User::factory()->driver()->create(['agency_id' => $agency->id, 'license_expiry_date' => now()->addYear()]); // Valid
        User::factory()->driver()->create(['agency_id' => $agency->id, 'license_expiry_date' => now()->addDays(10)]); // Expiring Soon
        User::factory()->driver()->create(['agency_id' => $agency->id, 'license_expiry_date' => now()->subDay()]); // Expired
        User::factory()->driver()->create(['agency_id' => $agency->id, 'license_expiry_date' => null]); // no license — excluded

        Sanctum::actingAs($admin);

        $this->getJson('/api/v1/licenses/monitoring')
            ->assertOk()
            ->assertJson(['valid' => 1, 'expiring_soon' => 1, 'expired' => 1]);
    }

    public function test_boundary_exactly_on_the_threshold_day_counts_as_expiring_soon(): void
    {
        $agency = Agency::factory()->create(['license_expiry_warning_days' => 30]);
        $admin = User::factory()->admin()->create(['agency_id' => $agency->id]);

        User::factory()->driver()->create(['agency_id' => $agency->id, 'license_expiry_date' => now()->addDays(30)]);

        Sanctum::actingAs($admin);

        $this->getJson('/api/v1/licenses/monitoring')
            ->assertOk()
            ->assertJson(['valid' => 0, 'expiring_soon' => 1, 'expired' => 0]);
    }

    public function test_one_day_past_the_threshold_counts_as_valid(): void
    {
        $agency = Agency::factory()->create(['license_expiry_warning_days' => 30]);
        $admin = User::factory()->admin()->create(['agency_id' => $agency->id]);

        User::factory()->driver()->create(['agency_id' => $agency->id, 'license_expiry_date' => now()->addDays(31)]);

        Sanctum::actingAs($admin);

        $this->getJson('/api/v1/licenses/monitoring')
            ->assertOk()
            ->assertJson(['valid' => 1, 'expiring_soon' => 0, 'expired' => 0]);
    }

    public function test_a_different_agencys_wider_threshold_does_not_leak(): void
    {
        $tight = Agency::factory()->create(['license_expiry_warning_days' => 7]);
        $wide = Agency::factory()->create(['license_expiry_warning_days' => 60]);
        $tightAdmin = User::factory()->admin()->create(['agency_id' => $tight->id]);

        // 20 days out: Expiring Soon under the wide agency's threshold, Valid under the tight one.
        User::factory()->driver()->create(['agency_id' => $tight->id, 'license_expiry_date' => now()->addDays(20)]);
        User::factory()->driver()->create(['agency_id' => $wide->id, 'license_expiry_date' => now()->addDays(20)]);

        Sanctum::actingAs($tightAdmin);

        $this->getJson('/api/v1/licenses/monitoring')
            ->assertOk()
            ->assertJson(['valid' => 1, 'expiring_soon' => 0, 'expired' => 0]);
    }

    public function test_pending_and_rejected_drivers_are_excluded(): void
    {
        $agency = Agency::factory()->create();
        $admin = User::factory()->admin()->create(['agency_id' => $agency->id]);

        User::factory()->driver()->pending()->create(['agency_id' => $agency->id, 'license_expiry_date' => now()->subDay()]);
        User::factory()->driver()->rejected()->create(['agency_id' => $agency->id, 'license_expiry_date' => now()->subDay()]);

        Sanctum::actingAs($admin);

        $this->getJson('/api/v1/licenses/monitoring')
            ->assertOk()
            ->assertJson(['valid' => 0, 'expiring_soon' => 0, 'expired' => 0]);
    }

    public function test_driver_token_is_refused(): void
    {
        Sanctum::actingAs(User::factory()->driver()->create());

        $this->getJson('/api/v1/licenses/monitoring')->assertForbidden();
    }
}
