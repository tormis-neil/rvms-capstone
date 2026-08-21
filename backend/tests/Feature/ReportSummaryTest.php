<?php

namespace Tests\Feature;

use App\Models\Agency;
use App\Models\DamageReport;
use App\Models\Dispatch;
use App\Models\Inspection;
use App\Models\InspectionChecklistItem;
use App\Models\InspectionItem;
use App\Models\PmSchedule;
use App\Models\RepairLog;
use App\Models\User;
use App\Models\Vehicle;
use App\Services\ReportBuilder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * Reports carry analysis, not only records (FR-20, 2026-08).
 *
 * Every report was a table of text, so an officer wanting to know which item
 * fails most often had to tally the rows by eye — the manual work the system
 * exists to remove. Each report now leads with headline figures and, where it
 * helps, one ranked breakdown.
 *
 * The property that matters most is the last one asserted here: the summary is
 * computed from the SAME records that produce the rows, so a filtered report
 * can never print figures describing a wider set than the table beneath them.
 * That would not be a cosmetic bug — it would be a printed government document
 * stating something untrue.
 */
class ReportSummaryTest extends TestCase
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
        $this->driver = User::factory()->driver()->create(['agency_id' => $this->agency->id]);
        $this->vehicle = Vehicle::factory()->create([
            'agency_id' => $this->agency->id,
            'plate_number' => 'BFP-0001',
            'status' => Vehicle::STATUS_OPERATIONAL,
        ]);

        $this->seed(\Database\Seeders\InspectionChecklistSeeder::class);
    }

    private function build(string $type, array $filters = []): array
    {
        return app(ReportBuilder::class)->build($type, $this->agency->id, $filters);
    }

    /** @return list<array{string}> */
    public static function everyReportType(): array
    {
        return array_map(fn (string $slug) => [$slug], array_keys(ReportBuilder::TYPES));
    }

    /**
     * Every report answers with the shape, even with nothing to report — an
     * empty report must print its zeroes rather than crash the page.
     */
    #[DataProvider('everyReportType')]
    public function test_every_report_carries_a_summary_even_when_empty(string $type): void
    {
        $report = $this->build($type);

        $this->assertArrayHasKey('summary', $report);
        $this->assertNotEmpty($report['summary']['stats'], "{$type} produced no headline figures.");

        foreach ($report['summary']['stats'] as $stat) {
            $this->assertArrayHasKey('label', $stat);
            $this->assertArrayHasKey('value', $stat);
        }
    }

    public function test_the_inspection_summary_counts_flagged_submissions_and_ranks_the_items(): void
    {
        $brakes = InspectionChecklistItem::query()->where('name', 'Brakes')->firstOrFail();
        $lights = InspectionChecklistItem::query()->where('name', 'Lights')->firstOrFail();

        // Two inspections flag Brakes, one flags Lights, one is all OK.
        $this->inspectionFlagging([$brakes->id]);
        $this->inspectionFlagging([$brakes->id]);
        $this->inspectionFlagging([$lights->id]);
        $this->inspectionFlagging([]);

        $summary = $this->build('inspections')['summary'];

        $this->assertSame('4', $this->statValue($summary, 'Inspections Submitted'));
        // 3 of the 4 carried at least one flagged item.
        $this->assertSame('3 of 4 (75%)', $this->statValue($summary, 'With Reported Issues'));

        $this->assertSame('Most Frequently Flagged Items', $summary['breakdown']['title']);
        $this->assertSame(
            [['label' => 'Brakes', 'count' => 2], ['label' => 'Lights', 'count' => 1]],
            $summary['breakdown']['items'],
        );
    }

    public function test_the_repair_summary_averages_only_repairs_that_carry_a_cost(): void
    {
        // Cost is optional (FR-13). Averaging over ALL repairs would understate
        // the figure every time somebody left it blank.
        $this->repair(1000.00);
        $this->repair(3000.00);
        $this->repair(null);

        $summary = $this->build('repairs-maintenance')['summary'];

        $this->assertSame('3', $this->statValue($summary, 'Repairs Logged'));
        $this->assertSame('₱4,000.00', $this->statValue($summary, 'Total Recorded Cost'));
        $this->assertSame('₱2,000.00', $this->statValue($summary, 'Average per Repair'));
    }

    public function test_a_repair_report_with_no_costs_at_all_says_so_rather_than_dividing_by_zero(): void
    {
        $this->repair(null);

        $summary = $this->build('repairs-maintenance')['summary'];

        $this->assertSame('—', $this->statValue($summary, 'Average per Repair'));
    }

    public function test_the_dispatch_summary_averages_only_closed_trips(): void
    {
        // Closed: two hours. Active: no end, so it must not enter the average —
        // treating "now" as its time in produces a duration that grows on every
        // page refresh.
        Dispatch::factory()->create([
            'agency_id' => $this->agency->id,
            'vehicle_id' => $this->vehicle->id,
            'driver_id' => $this->driver->id,
            'time_out' => now()->subHours(5),
            'time_in' => now()->subHours(3),
        ]);
        Dispatch::factory()->create([
            'agency_id' => $this->agency->id,
            'vehicle_id' => $this->vehicle->id,
            'driver_id' => $this->driver->id,
            'time_out' => now()->subHours(9),
            'time_in' => null,
        ]);

        $summary = $this->build('dispatch')['summary'];

        $this->assertSame('2', $this->statValue($summary, 'Dispatches'));
        $this->assertSame('1', $this->statValue($summary, 'Still Active'));
        $this->assertSame('2h 0m', $this->statValue($summary, 'Average Duration'));
    }

    /**
     * The fleet report's ranking is by vehicle TYPE (2026-08).
     *
     * It used to rank the four statuses, which was the wrong shape — the
     * statuses divide a whole and are now the composition bar. What a ranking
     * usefully says about a fleet is what it is made of.
     */
    public function test_the_fleet_ranking_is_by_vehicle_type(): void
    {
        // Three against one, so the ranking cannot tie with the vehicle created
        // in setUp() — its type is random, and a tie makes the order arbitrary.
        Vehicle::factory()->count(3)->create(['agency_id' => $this->agency->id, 'type' => 'Fire Truck']);
        Vehicle::factory()->create(['agency_id' => $this->agency->id, 'type' => 'Ambulance']);

        $breakdown = $this->build('vehicle-status')['summary']['breakdown'];

        $this->assertSame('Fleet by Vehicle Type', $breakdown['title']);
        $this->assertSame('Fire Truck', $breakdown['items'][0]['label']);
        $this->assertGreaterThanOrEqual(3, $breakdown['items'][0]['count']);
    }

    /** A breakdown with nothing in it is omitted, not printed empty. */
    public function test_a_breakdown_with_no_data_is_omitted(): void
    {
        $this->assertNull($this->build('damage')['summary']['breakdown']);
    }

    /* ------------------------- compositions ------------------------------ */

    /**
     * Four reports were drawing a composition as a ranking (2026-08,
     * lead-reported that all six looked the same). A composition answers a
     * different question and has to look different: one stacked bar totalling
     * 100%, in documented order rather than size order.
     */
    #[DataProvider('everyReportType')]
    public function test_every_report_declares_the_composition_key(string $type): void
    {
        $this->assertArrayHasKey('composition', $this->build($type)['summary']);
    }

    public function test_the_fleet_availability_bar_totals_one_hundred_percent(): void
    {
        Vehicle::factory()->create(['agency_id' => $this->agency->id, 'status' => Vehicle::STATUS_DISPATCHED]);
        Vehicle::factory()->create(['agency_id' => $this->agency->id, 'status' => Vehicle::STATUS_NOT_OPERATIONAL]);
        Vehicle::factory()->create(['agency_id' => $this->agency->id, 'status' => Vehicle::STATUS_UNDER_PM]);
        // Plus the Operational one from setUp() — four vehicles, one per status.

        $composition = $this->build('vehicle-status')['summary']['composition'];

        $this->assertSame('Fleet Availability', $composition['title']);
        $this->assertSame(100.0, array_sum(array_column($composition['segments'], 'percent')));

        // Documented order, NOT size order — the four statuses read as a fleet,
        // not as a league table.
        $this->assertSame(Vehicle::STATUSES, array_column($composition['segments'], 'label'));

        foreach ($composition['segments'] as $segment) {
            $this->assertSame(25.0, $segment['percent']);
        }
    }

    /** A status at zero keeps its place in the key: that is a finding, not a gap. */
    public function test_a_status_at_zero_is_still_named(): void
    {
        $composition = $this->build('vehicle-status')['summary']['composition'];

        $notOperational = collect($composition['segments'])
            ->firstWhere('label', Vehicle::STATUS_NOT_OPERATIONAL);

        $this->assertNotNull($notOperational);
        $this->assertSame(0.0, $notOperational['percent']);
    }

    /**
     * Repairs divide by COST, not by count (2026-08). "Seven repairs were
     * external" is trivia; the share of the money is the finding a government
     * report exists to show.
     */
    public function test_repairs_divide_the_money_not_the_count(): void
    {
        // Three cheap in-house jobs against one expensive external one: by
        // count the external share is 25%, by cost it is 80%. The report must
        // say 80%.
        $this->repair(1000.00, RepairLog::SOURCE_INTERNAL);
        $this->repair(500.00, RepairLog::SOURCE_INTERNAL);
        $this->repair(500.00, RepairLog::SOURCE_GSO);
        $this->repair(8000.00, RepairLog::SOURCE_EXTERNAL);

        $composition = $this->build('repairs-maintenance')['summary']['composition'];

        $this->assertSame('Where the Money Went', $composition['title']);

        $external = collect($composition['segments'])->firstWhere('label', RepairLog::SOURCE_EXTERNAL);

        $this->assertSame(80.0, $external['percent']);
        $this->assertSame('₱8,000.00', $external['display']);
    }

    /** Repairs with no cost cannot be drawn, so the report says how many. */
    public function test_repairs_without_a_cost_are_declared_not_silently_dropped(): void
    {
        $this->repair(1000.00, RepairLog::SOURCE_GSO);
        $this->repair(null, RepairLog::SOURCE_GSO);

        $note = $this->build('repairs-maintenance')['summary']['composition']['note'];

        $this->assertStringContainsString('1 repair', $note);
        $this->assertStringContainsString('no recorded cost', $note);
    }

    /**
     * A composition of nothing is a rendering artefact, not a statement — an
     * empty report must omit the bar rather than draw zero-width segments.
     */
    public function test_a_composition_with_no_data_is_omitted(): void
    {
        $this->assertNull($this->build('damage')['summary']['composition']);
        $this->assertNull($this->build('repairs-maintenance')['summary']['composition']);
    }

    public function test_the_page_renders_the_composition_bar_and_its_key(): void
    {
        $this->repair(2000.00, RepairLog::SOURCE_EXTERNAL);

        $html = $this->actingAs($this->admin)
            ->get('/reports?type=repairs-maintenance&range=all')
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString('Where the Money Went', $html);
        $this->assertStringContainsString('composition-bar', $html);
        $this->assertStringContainsString('composition-key', $html);
        // Still no canvas — a report has to print (FR-20).
        $this->assertStringNotContainsString('<canvas', $html);
    }

    /* --------------- the property that matters most ---------------------- */

    /**
     * The summary must describe exactly the rows printed beneath it.
     *
     * A figure computed from a second, unfiltered query would print a total
     * that the table underneath visibly contradicts — on a government document
     * carrying the generating administrator's name.
     */
    public function test_the_summary_describes_only_the_filtered_rows(): void
    {
        $other = Vehicle::factory()->create([
            'agency_id' => $this->agency->id,
            'plate_number' => 'BFP-0002',
        ]);

        DamageReport::factory()->count(3)->create([
            'agency_id' => $this->agency->id,
            'vehicle_id' => $this->vehicle->id,
            'driver_id' => $this->driver->id,
        ]);
        DamageReport::factory()->count(2)->create([
            'agency_id' => $this->agency->id,
            'vehicle_id' => $other->id,
            'driver_id' => $this->driver->id,
        ]);

        $report = $this->build('damage', ['vehicle_id' => $this->vehicle->id]);

        $this->assertCount(3, $report['rows']);
        $this->assertSame('3', $this->statValue($report['summary'], 'Reports Filed'));
        // And the breakdown names only the vehicle that was filtered to.
        $this->assertSame(
            [['label' => 'BFP-0001', 'count' => 3]],
            $report['summary']['breakdown']['items'],
        );
    }

    /** Another agency's records never reach the figures (FR-02). */
    public function test_the_summary_never_counts_another_agencys_records(): void
    {
        $foreign = Agency::factory()->create(['code' => 'PNP']);
        $foreignVehicle = Vehicle::factory()->create(['agency_id' => $foreign->id]);
        DamageReport::factory()->count(4)->create([
            'agency_id' => $foreign->id,
            'vehicle_id' => $foreignVehicle->id,
            'driver_id' => User::factory()->driver()->create(['agency_id' => $foreign->id])->id,
        ]);

        $this->assertSame('0', $this->statValue($this->build('damage')['summary'], 'Reports Filed'));
    }

    /* -------------------------- the page itself --------------------------- */

    public function test_the_dashboard_prints_the_figures_above_the_table(): void
    {
        $this->repair(1500.00);

        $html = $this->actingAs($this->admin)
            ->get('/reports?type=repairs-maintenance&range=all')
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString('Repairs Logged', $html);
        $this->assertStringContainsString('₱1,500.00', $html);
        $this->assertStringContainsString('Vehicles Most Repaired', $html);
        // Server-rendered bars, not a canvas — a report has to print (FR-20).
        $this->assertStringContainsString('progress-bar', $html);
        $this->assertStringNotContainsString('<canvas', $html);
    }

    /* ------------------------------ helpers ------------------------------ */

    private function statValue(array $summary, string $label): string
    {
        foreach ($summary['stats'] as $stat) {
            if ($stat['label'] === $label) {
                return $stat['value'];
            }
        }

        $this->fail("No headline figure labelled \"{$label}\". Present: "
            .implode(', ', array_column($summary['stats'], 'label')));
    }

    private function inspectionFlagging(array $flaggedItemIds): Inspection
    {
        $inspection = Inspection::factory()->create([
            'agency_id' => $this->agency->id,
            'vehicle_id' => $this->vehicle->id,
            'driver_id' => $this->driver->id,
            'inspection_date' => now()->toDateString(),
        ]);

        foreach (InspectionChecklistItem::query()->forAgencyCode('BFP')->get() as $item) {
            $flagged = in_array($item->id, $flaggedItemIds, true);

            InspectionItem::query()->create([
                'inspection_id' => $inspection->id,
                'checklist_item_id' => $item->id,
                'status' => $flagged ? InspectionItem::STATUS_HAS_ISSUE : InspectionItem::STATUS_OK,
                'remarks' => $flagged ? 'Needs attention.' : null,
            ]);
        }

        return $inspection;
    }

    private function repair(?float $cost, string $source = RepairLog::SOURCE_GSO): RepairLog
    {
        return RepairLog::factory()->create([
            'agency_id' => $this->agency->id,
            'vehicle_id' => $this->vehicle->id,
            'driver_id' => $this->driver->id,
            'repair_date' => now()->toDateString(),
            'cost' => $cost,
            'repair_source' => $source,
        ]);
    }
}
