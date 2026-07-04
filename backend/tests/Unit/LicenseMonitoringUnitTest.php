<?php

namespace Tests\Unit;

use App\Models\Agency;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class LicenseMonitoringUnitTest extends TestCase
{
    use RefreshDatabase;

    public function test_expired_scope_selects_only_past_dates(): void
    {
        $agency = Agency::factory()->create();
        $expired = User::factory()->driver()->create([
            'agency_id' => $agency->id,
            'license_expiry_date' => Carbon::today()->subDay()->toDateString(),
        ]);
        User::factory()->driver()->create([
            'agency_id' => $agency->id,
            'license_expiry_date' => Carbon::today()->addDays(10)->toDateString(),
        ]);

        $ids = User::drivers()->where('agency_id', $agency->id)->expired()->pluck('id');

        $this->assertTrue($ids->contains($expired->id));
        $this->assertCount(1, $ids);
    }

    public function test_expiring_soon_respects_the_threshold_boundary(): void
    {
        $agency = Agency::factory()->create(['license_expiry_warning_days' => 30]);

        // Exactly on the 30-day boundary → included.
        $onBoundary = User::factory()->driver()->create([
            'agency_id' => $agency->id,
            'license_expiry_date' => Carbon::today()->addDays(30)->toDateString(),
        ]);
        // One day past the window → excluded.
        User::factory()->driver()->create([
            'agency_id' => $agency->id,
            'license_expiry_date' => Carbon::today()->addDays(31)->toDateString(),
        ]);
        // Already expired → not "expiring soon".
        User::factory()->driver()->create([
            'agency_id' => $agency->id,
            'license_expiry_date' => Carbon::today()->subDay()->toDateString(),
        ]);

        $ids = User::drivers()->where('agency_id', $agency->id)
            ->expiringSoon($agency->license_expiry_warning_days)
            ->pluck('id');

        $this->assertSame([$onBoundary->id], $ids->all());
    }

    public function test_today_counts_as_expiring_soon_not_expired(): void
    {
        $agency = Agency::factory()->create(['license_expiry_warning_days' => 30]);
        $today = User::factory()->driver()->create([
            'agency_id' => $agency->id,
            'license_expiry_date' => Carbon::today()->toDateString(),
        ]);

        $expiringIds = User::drivers()->where('agency_id', $agency->id)->expiringSoon(30)->pluck('id');
        $expiredIds = User::drivers()->where('agency_id', $agency->id)->expired()->pluck('id');

        $this->assertTrue($expiringIds->contains($today->id));
        $this->assertFalse($expiredIds->contains($today->id));
    }

    public function test_monitoring_endpoint_returns_only_callers_agency(): void
    {
        $agency = Agency::factory()->create(['license_expiry_warning_days' => 30]);
        $admin = User::factory()->admin()->create(['agency_id' => $agency->id]);

        User::factory()->driver()->create([
            'agency_id' => $agency->id,
            'license_expiry_date' => Carbon::today()->addDays(5)->toDateString(),
        ]);
        // Another agency's expiring driver must not leak in.
        User::factory()->driver()->create([
            'license_expiry_date' => Carbon::today()->addDays(5)->toDateString(),
        ]);

        \Laravel\Sanctum\Sanctum::actingAs($admin);

        $this->getJson('/api/v1/licenses/monitoring')
            ->assertOk()
            ->assertJsonPath('warning_days', 30)
            ->assertJsonPath('expiring_soon_count', 1)
            ->assertJsonCount(1, 'expiring_soon');
    }
}
