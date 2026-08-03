<?php

namespace Tests\Feature;

use App\Models\Agency;
use App\Models\DamageReport;
use App\Models\Dispatch;
use App\Models\Inspection;
use App\Models\Notification;
use App\Models\PmSchedule;
use App\Models\User;
use App\Models\Vehicle;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * R10 sub-task 4 — pagination + the N+1 audit, as assertions (NFR-01).
 *
 * Two claims, each provable:
 *
 *  1. Long lists arrive in pages. Every growing page now carries the
 *     card-footer pager (the convention vehicles/drivers/repairs established
 *     in R2/R4), and a second page really holds the older rows.
 *
 *  2. Rendering a page costs a FIXED number of queries. An N+1 hides at small
 *     sizes and surfaces in production, so the test renders each page at two
 *     different row counts and asserts the query count did not move — the
 *     shape of the proof matters more than any particular number, which is
 *     why no absolute count is pinned.
 *
 * Reports are deliberately absent: a printout with a pager is not a printout
 * (see ReportBuilder) — they are excluded from pagination BY DESIGN.
 */
class PaginationAndQueryCountTest extends TestCase
{
    use RefreshDatabase;

    private Agency $agency;

    private User $admin;

    private User $driver;

    private Vehicle $vehicle;

    protected function setUp(): void
    {
        parent::setUp();
        $this->agency = Agency::factory()->create(['code' => 'BFP']);
        $this->admin = User::factory()->admin()->create(['agency_id' => $this->agency->id]);
        $this->driver = User::factory()->driver()->create([
            'agency_id' => $this->agency->id,
            'license_expiry_date' => now()->addYear(),
        ]);
        $this->vehicle = Vehicle::factory()->create([
            'agency_id' => $this->agency->id,
            'assigned_driver_id' => $this->driver->id,
        ]);
    }

    /**
     * Render a page and count the queries it cost.
     *
     * A FRESH admin instance each time, deliberately: `actingAs` keeps the
     * same in-memory model, so a second call would find its `agency` relation
     * already loaded and skip one query — making the later measurement look
     * cheaper than the first for reasons that do not exist in production.
     */
    private function queryCount(string $uri): int
    {
        DB::flushQueryLog();
        DB::enableQueryLog();
        $this->actingAs($this->admin->fresh())->get($uri)->assertOk();
        $count = count(DB::getQueryLog());
        DB::disableQueryLog();

        return $count;
    }

    /* --------------------------- constant queries ------------------------- */

    public function test_the_drivers_page_query_count_does_not_grow_with_drivers(): void
    {
        User::factory()->driver()->count(3)->create([
            'agency_id' => $this->agency->id, 'license_expiry_date' => now()->addYear(),
        ]);
        $small = $this->queryCount('/drivers');

        // licenseStatus() reads agency.license_expiry_warning_days per row —
        // the N+1 this phase fixed with an eager load. Twelve more drivers
        // must not cost twelve more queries.
        User::factory()->driver()->count(12)->create([
            'agency_id' => $this->agency->id, 'license_expiry_date' => now()->addYear(),
        ]);
        $large = $this->queryCount('/drivers');

        $this->assertSame($small, $large, "Drivers page: {$small} queries at few rows, {$large} at many — an N+1 is back.");
    }

    public function test_the_inspections_page_query_count_does_not_grow_with_records(): void
    {
        $make = fn (int $n) => Inspection::factory()->count($n)->create([
            'agency_id' => $this->agency->id, 'vehicle_id' => $this->vehicle->id, 'driver_id' => $this->driver->id,
        ]);

        $make(2);
        $small = $this->queryCount('/inspections');

        $make(10);
        $large = $this->queryCount('/inspections');

        $this->assertSame($small, $large, "Inspections page: {$small} vs {$large} queries — an N+1 crept in.");
    }

    public function test_the_dispatch_page_query_count_does_not_grow_with_records(): void
    {
        $make = fn (int $n) => Dispatch::factory()->count($n)->create([
            'agency_id' => $this->agency->id, 'vehicle_id' => $this->vehicle->id, 'driver_id' => $this->driver->id,
        ]);

        $make(2);
        $small = $this->queryCount('/dispatch');

        $make(10);
        $large = $this->queryCount('/dispatch');

        $this->assertSame($small, $large, "Dispatch page: {$small} vs {$large} queries — an N+1 crept in.");
    }

    public function test_the_dashboard_query_count_does_not_grow_with_records(): void
    {
        $damage = fn (int $n) => DamageReport::factory()->count($n)->create([
            'agency_id' => $this->agency->id, 'vehicle_id' => $this->vehicle->id,
            'driver_id' => $this->driver->id, 'status' => DamageReport::STATUS_PENDING,
        ]);

        // BOTH measurements start non-empty. Laravel skips an eager load
        // entirely when the parent collection is empty, so comparing "no
        // records" against "some records" would show one extra query that is
        // fixed overhead, not an N+1 — measuring 2 rows against 12 isolates
        // the growth that actually matters.
        Vehicle::factory()->count(2)->create(['agency_id' => $this->agency->id]);
        $damage(2);
        $small = $this->queryCount('/dashboard');

        Vehicle::factory()->count(10)->create(['agency_id' => $this->agency->id]);
        $damage(10);
        $large = $this->queryCount('/dashboard');

        $this->assertSame($small, $large, "Dashboard: {$small} vs {$large} queries — an N+1 crept in.");
    }

    /* ------------------------------ pagination ---------------------------- */

    public function test_the_dispatch_page_paginates_at_ten(): void
    {
        Dispatch::factory()->count(13)->create([
            'agency_id' => $this->agency->id, 'vehicle_id' => $this->vehicle->id, 'driver_id' => $this->driver->id,
        ]);

        $this->actingAs($this->admin)->get('/dispatch')->assertOk()
            ->assertSee('Showing 1 to 10 of 13 dispatches');

        $this->actingAs($this->admin)->get('/dispatch?page=2')->assertOk()
            ->assertSee('Showing 11 to 13 of 13 dispatches');
    }

    /** The active banner counts the TRUE total, not the visible page. */
    public function test_the_dispatch_active_banner_survives_pagination(): void
    {
        Dispatch::factory()->count(12)->create([
            'agency_id' => $this->agency->id, 'vehicle_id' => $this->vehicle->id,
            'driver_id' => $this->driver->id, 'time_in' => null,
        ]);

        $this->actingAs($this->admin)->get('/dispatch?page=2')->assertOk()
            ->assertSee('<strong>12</strong>', false);
    }

    public function test_the_inspections_page_paginates_both_tables_independently(): void
    {
        Inspection::factory()->count(12)->create([
            'agency_id' => $this->agency->id, 'vehicle_id' => $this->vehicle->id, 'driver_id' => $this->driver->id,
        ]);
        DamageReport::factory()->count(11)->create([
            'agency_id' => $this->agency->id, 'vehicle_id' => $this->vehicle->id, 'driver_id' => $this->driver->id,
        ]);

        // Page 2 of inspections leaves the damage table on ITS page 1.
        $this->actingAs($this->admin)->get('/inspections?inspections_page=2')->assertOk()
            ->assertSee('Showing 11 to 12 of 12 inspections')
            ->assertSee('Showing 1 to 10 of 11 damage reports');
    }

    /** The pending pill is the true total even from page 2. */
    public function test_the_pending_pill_is_not_trimmed_to_the_page(): void
    {
        Inspection::factory()->count(12)->create([
            'agency_id' => $this->agency->id, 'vehicle_id' => $this->vehicle->id,
            'driver_id' => $this->driver->id, 'review_status' => Inspection::STATUS_PENDING,
        ]);

        $this->actingAs($this->admin)->get('/inspections?inspections_page=2')->assertOk()
            ->assertSee('12 Pending Review');
    }

    public function test_the_pm_page_paginates_each_tab_with_its_own_parameter(): void
    {
        PmSchedule::factory()->count(11)->create([
            'agency_id' => $this->agency->id, 'vehicle_id' => $this->vehicle->id,
        ]);
        PmSchedule::factory()->completed()->count(12)->create([
            'agency_id' => $this->agency->id, 'vehicle_id' => $this->vehicle->id,
        ]);

        $this->actingAs($this->admin)->get('/pm?completed_page=2')->assertOk()
            ->assertSee('Showing 1 to 10 of 11 active schedules')
            ->assertSee('Showing 11 to 12 of 12 completed records');
    }

    /** The Active tab badge shows all schedules, not the page's ten. */
    public function test_the_pm_tab_badge_is_the_true_total(): void
    {
        PmSchedule::factory()->count(11)->create([
            'agency_id' => $this->agency->id, 'vehicle_id' => $this->vehicle->id,
        ]);

        $this->actingAs($this->admin)->get('/pm')->assertOk()
            ->assertSee('Active Schedules <span class="badge bg-navy text-white ms-1">11</span>', false);
    }

    public function test_the_notifications_page_paginates_at_twenty(): void
    {
        Notification::factory()->count(23)->create([
            'agency_id' => $this->agency->id, 'user_id' => $this->admin->id,
        ]);

        $this->actingAs($this->admin)->get('/notifications')->assertOk()
            ->assertSee('Showing 1 to 20 of 23 notifications');

        $this->actingAs($this->admin)->get('/notifications?page=2')->assertOk()
            ->assertSee('Showing 21 to 23 of 23 notifications');
    }

    /** Mark All as Read still clears EVERYTHING, not just the visible page. */
    public function test_mark_all_read_reaches_beyond_the_current_page(): void
    {
        Notification::factory()->count(25)->create([
            'agency_id' => $this->agency->id, 'user_id' => $this->admin->id, 'is_read' => false,
        ]);

        $this->actingAs($this->admin)->patch('/notifications/read-all');

        $this->assertSame(0, Notification::query()->forUser($this->admin->id)->unread()->count());
    }

    /** API lists were paginated from their phases — pin one representative. */
    public function test_the_api_lists_stay_paginated(): void
    {
        Vehicle::factory()->count(12)->create(['agency_id' => $this->agency->id]);

        $this->actingAs($this->admin, 'sanctum')
            ->getJson('/api/v1/vehicles')
            ->assertOk()
            ->assertJsonCount(10, 'data')
            ->assertJsonPath('meta.total', 13); // 12 + the setUp vehicle
    }
}
