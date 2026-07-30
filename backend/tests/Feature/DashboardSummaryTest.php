<?php

namespace Tests\Feature;

use App\Models\Agency;
use App\Models\DamageReport;
use App\Models\Inspection;
use App\Models\User;
use App\Models\Vehicle;
use App\Services\DashboardSummary;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * R8 Day 14 — the Fleet Overview figures (FR-19).
 *
 * FR-19 calls these counts "real-time", which in practice means they are
 * derived from the records themselves on every request — so the tests below
 * check the arithmetic AND, just as importantly, that no figure ever reaches
 * across the agency boundary.
 */
class DashboardSummaryTest extends TestCase
{
    use RefreshDatabase;

    private Agency $agency;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->agency = Agency::factory()->create(['code' => 'BFP', 'license_expiry_warning_days' => 30]);
        $this->admin = User::factory()->admin()->create(['agency_id' => $this->agency->id]);
    }

    private function vehicle(string $status, ?Agency $agency = null): Vehicle
    {
        return Vehicle::factory()->create([
            'agency_id' => ($agency ?? $this->agency)->id,
            'status' => $status,
        ]);
    }

    public function test_the_eight_counts_match_the_records(): void
    {
        $this->vehicle(Vehicle::STATUS_OPERATIONAL);
        $this->vehicle(Vehicle::STATUS_OPERATIONAL);
        $this->vehicle(Vehicle::STATUS_DISPATCHED);
        $this->vehicle(Vehicle::STATUS_UNDER_PM);
        $vehicle = $this->vehicle(Vehicle::STATUS_NOT_OPERATIONAL);

        User::factory()->driver()->count(3)->create([
            'agency_id' => $this->agency->id,
            'license_expiry_date' => now()->addYear(),
        ]);

        DamageReport::factory()->count(2)->create([
            'agency_id' => $this->agency->id,
            'vehicle_id' => $vehicle->id,
            'driver_id' => User::factory()->driver()->create(['agency_id' => $this->agency->id])->id,
            'status' => DamageReport::STATUS_PENDING,
        ]);

        $counts = app(DashboardSummary::class)->counts($this->agency->id);

        $this->assertSame(5, $counts['total_vehicles']);
        $this->assertSame(2, $counts['operational']);
        $this->assertSame(1, $counts['dispatched']);
        $this->assertSame(1, $counts['under_pm']);
        $this->assertSame(1, $counts['not_operational']);
        $this->assertSame(4, $counts['total_drivers']); // 3 + the damage reporter
        $this->assertSame(2, $counts['pending_damage_reports']);
    }

    /** The four status counts must always add up to the total. */
    public function test_the_status_counts_sum_to_the_total(): void
    {
        foreach (Vehicle::STATUSES as $status) {
            $this->vehicle($status);
        }

        $counts = app(DashboardSummary::class)->counts($this->agency->id);

        $this->assertSame(
            $counts['total_vehicles'],
            $counts['operational'] + $counts['dispatched'] + $counts['under_pm'] + $counts['not_operational'],
        );
    }

    /**
     * The card counts the warning window only, so it matches the Drivers
     * page's card of the same name — an already-expired licence is a different
     * (worse) state and is surfaced in the Action Required list instead.
     */
    public function test_expiring_licenses_counts_the_warning_window_not_the_expired(): void
    {
        User::factory()->driver()->create([
            'agency_id' => $this->agency->id, 'license_expiry_date' => now()->addDays(10),
        ]);
        User::factory()->driver()->create([
            'agency_id' => $this->agency->id, 'license_expiry_date' => now()->subDay(),
        ]);
        User::factory()->driver()->create([
            'agency_id' => $this->agency->id, 'license_expiry_date' => now()->addYear(),
        ]);

        $summary = app(DashboardSummary::class);

        $this->assertSame(1, $summary->counts($this->agency->id)['expiring_licenses']);
        // …but the list covers both, so the expired one is never hidden.
        $this->assertSame(2, $summary->expiringLicenseAttentionCount($this->agency->id));
    }

    /** The threshold is a column, so a different agency window shifts the count. */
    public function test_the_agency_threshold_drives_the_expiring_count(): void
    {
        $this->agency->update(['license_expiry_warning_days' => 7]);

        User::factory()->driver()->create([
            'agency_id' => $this->agency->id, 'license_expiry_date' => now()->addDays(10),
        ]);

        $this->assertSame(0, app(DashboardSummary::class)->counts($this->agency->id)['expiring_licenses']);

        $this->agency->update(['license_expiry_warning_days' => 30]);

        $this->assertSame(1, app(DashboardSummary::class)->counts($this->agency->id)['expiring_licenses']);
    }

    /** Pending inspections AND pending damage — what is waiting on the admin. */
    public function test_pending_reviews_cover_both_inspections_and_damage(): void
    {
        $vehicle = $this->vehicle(Vehicle::STATUS_OPERATIONAL);
        $driver = User::factory()->driver()->create(['agency_id' => $this->agency->id]);

        DamageReport::factory()->create([
            'agency_id' => $this->agency->id, 'vehicle_id' => $vehicle->id,
            'driver_id' => $driver->id, 'status' => DamageReport::STATUS_PENDING,
            'nature_of_damage' => 'Cracked side mirror',
        ]);
        Inspection::factory()->create([
            'agency_id' => $this->agency->id, 'vehicle_id' => $vehicle->id,
            'driver_id' => $driver->id, 'review_status' => Inspection::STATUS_PENDING,
        ]);
        Inspection::factory()->create([
            'agency_id' => $this->agency->id, 'vehicle_id' => $vehicle->id,
            'driver_id' => $driver->id, 'review_status' => Inspection::STATUS_REVIEWED,
        ]);

        $summary = app(DashboardSummary::class);

        $this->assertSame(2, $summary->pendingReviewCount($this->agency->id));
        $this->assertCount(2, $summary->pendingReviews($this->agency->id));
    }

    /**
     * The pill shows the true total while the list shows the most recent few,
     * so a busy agency is never told a number quietly trimmed to fit.
     */
    public function test_the_list_is_capped_but_the_count_is_not(): void
    {
        $vehicle = $this->vehicle(Vehicle::STATUS_OPERATIONAL);
        $driver = User::factory()->driver()->create(['agency_id' => $this->agency->id]);

        DamageReport::factory()->count(8)->create([
            'agency_id' => $this->agency->id, 'vehicle_id' => $vehicle->id,
            'driver_id' => $driver->id, 'status' => DamageReport::STATUS_PENDING,
        ]);

        $summary = app(DashboardSummary::class);

        $this->assertSame(8, $summary->pendingReviewCount($this->agency->id));
        $this->assertCount(DashboardSummary::ACTION_LIST_LIMIT, $summary->pendingReviews($this->agency->id));
    }

    /* ----------------------------- the API ------------------------------- */

    public function test_the_api_returns_the_same_eight_counts(): void
    {
        $this->vehicle(Vehicle::STATUS_OPERATIONAL);

        $this->actingAs($this->admin, 'sanctum')
            ->getJson('/api/v1/dashboard/summary')
            ->assertOk()
            ->assertJsonStructure(['data' => [
                'total_vehicles', 'operational', 'dispatched', 'under_pm',
                'not_operational', 'total_drivers', 'expiring_licenses', 'pending_damage_reports',
            ]])
            ->assertJsonPath('data.total_vehicles', 1)
            ->assertJsonPath('data.operational', 1);
    }

    /** Fleet-wide totals are an administrator's business, not a driver's. */
    public function test_a_driver_cannot_read_the_summary(): void
    {
        $driver = User::factory()->driver()->create(['agency_id' => $this->agency->id]);

        $this->actingAs($driver, 'sanctum')
            ->getJson('/api/v1/dashboard/summary')
            ->assertForbidden();
    }

    public function test_the_summary_requires_authentication(): void
    {
        $this->getJson('/api/v1/dashboard/summary')->assertUnauthorized();
    }

    /* --------------------------- the agency wall -------------------------- */

    public function test_no_figure_counts_another_agencys_records(): void
    {
        $other = Agency::factory()->create(['code' => 'CHO']);
        $otherVehicle = $this->vehicle(Vehicle::STATUS_OPERATIONAL, $other);
        $otherDriver = User::factory()->driver()->create([
            'agency_id' => $other->id, 'license_expiry_date' => now()->addDays(5),
        ]);
        DamageReport::factory()->create([
            'agency_id' => $other->id, 'vehicle_id' => $otherVehicle->id,
            'driver_id' => $otherDriver->id, 'status' => DamageReport::STATUS_PENDING,
        ]);

        $counts = app(DashboardSummary::class)->counts($this->agency->id);

        $this->assertSame(0, $counts['total_vehicles']);
        $this->assertSame(0, $counts['total_drivers']);
        $this->assertSame(0, $counts['expiring_licenses']);
        $this->assertSame(0, $counts['pending_damage_reports']);
        $this->assertSame(0, app(DashboardSummary::class)->pendingReviewCount($this->agency->id));
    }
}
