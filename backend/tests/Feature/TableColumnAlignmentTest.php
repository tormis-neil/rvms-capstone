<?php

namespace Tests\Feature;

use App\Models\Agency;
use App\Models\DamageReport;
use App\Models\Dispatch;
use App\Models\Inspection;
use App\Models\InspectionChecklistItem;
use App\Models\PmSchedule;
use App\Models\RepairLog;
use App\Models\User;
use App\Models\Vehicle;
use Database\Seeders\InspectionChecklistSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Every dashboard table must render exactly as many body cells as it declares
 * header columns. The repairs table shipped with 9 headers but 8 cells (a defect
 * inherited from the prototype), which pushed every cell after REMARKS one
 * column left so the action buttons appeared under VEHICLE STATUS when the table
 * was scrolled sideways. This guards all of them against that class of bug.
 */
class TableColumnAlignmentTest extends TestCase
{
    use RefreshDatabase;

    private Agency $agency;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(InspectionChecklistSeeder::class);

        $this->agency = Agency::factory()->create(['code' => 'BFP']);
        $this->admin = User::factory()->admin()->create(['agency_id' => $this->agency->id]);
        $driver = User::factory()->driver()->create(['agency_id' => $this->agency->id]);
        $vehicle = Vehicle::factory()->create([
            'agency_id' => $this->agency->id,
            'assigned_driver_id' => $driver->id,
        ]);

        // One row in every table so each has a real body row to measure.
        $inspection = Inspection::factory()->create([
            'agency_id' => $this->agency->id, 'vehicle_id' => $vehicle->id, 'driver_id' => $driver->id,
        ]);
        foreach (InspectionChecklistItem::forAgencyCode('BFP')->get() as $item) {
            $inspection->items()->create(['checklist_item_id' => $item->id, 'status' => 'OK']);
        }

        DamageReport::factory()->create([
            'agency_id' => $this->agency->id, 'vehicle_id' => $vehicle->id, 'driver_id' => $driver->id,
        ]);
        RepairLog::factory()->create(['agency_id' => $this->agency->id, 'vehicle_id' => $vehicle->id]);
        PmSchedule::factory()->create(['agency_id' => $this->agency->id, 'vehicle_id' => $vehicle->id]);
        PmSchedule::factory()->completed()->create(['agency_id' => $this->agency->id, 'vehicle_id' => $vehicle->id]);
        Dispatch::factory()->create([
            'agency_id' => $this->agency->id, 'vehicle_id' => $vehicle->id, 'driver_id' => $driver->id,
        ]);
    }

    public static function pages(): array
    {
        return [
            'vehicles' => ['/vehicles'],
            'drivers' => ['/drivers'],
            'inspections & damage' => ['/inspections'],
            'repairs' => ['/repairs'],
            'pm schedules' => ['/pm'],
            'dispatch' => ['/dispatch'],
            // A report is printed and filed, so a column shifted one place left
            // survives on paper in a way a screen misalignment does not. Every
            // module already has a seeded row above, so all six render rows.
            'report — inspections' => ['/reports?type=inspections'],
            'report — damage' => ['/reports?type=damage'],
            'report — repairs' => ['/reports?type=repairs-maintenance'],
            'report — pm' => ['/reports?type=pm'],
            'report — dispatch' => ['/reports?type=dispatch'],
            'report — vehicle status' => ['/reports?type=vehicle-status'],
        ];
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('pages')]
    public function test_every_table_body_row_has_one_cell_per_header(string $url): void
    {
        $html = $this->actingAs($this->admin)->get($url)->assertOk()->getContent();

        preg_match_all('#<thead.*?</thead>#s', $html, $heads);
        preg_match_all('#<tbody.*?</tbody>#s', $html, $bodies);

        $this->assertNotEmpty($heads[0], "No table found on {$url}");

        foreach ($heads[0] as $i => $head) {
            $headerCells = preg_match_all('#<th[\s>]#', $head);

            // The first body row that actually renders data cells.
            preg_match('#<tr[^>]*>.*?</tr>#s', $bodies[0][$i] ?? '', $row);
            $bodyCells = isset($row[0]) ? preg_match_all('#<td[\s>]#', $row[0]) : 0;

            // An "empty state" row legitimately uses a single colspan cell.
            if ($bodyCells === 1 && str_contains($row[0] ?? '', 'colspan')) {
                continue;
            }

            $this->assertSame(
                $headerCells,
                $bodyCells,
                "Table #".($i + 1)." on {$url}: {$headerCells} header columns but {$bodyCells} body cells."
            );
        }
    }

    /**
     * Every ACTIONS cell keeps its buttons on one line (2026-08).
     *
     * A second prototype defect of the same family as the repairs header
     * mismatch above: the action buttons sit in a `text-end` cell with no
     * wrapper and no gap utility, so a narrow column wraps the last one onto
     * its own line and right-aligns it underneath. Inspections showed it first
     * because "View Checklist" + "Review" are the longest labels, but every
     * table shares the markup and therefore the bug.
     *
     * One flex container fixes all of them and — the reason this test exists —
     * makes the spacing identical on every page, which a per-page fix would
     * not. Reports are excluded: they are printouts and carry no buttons.
     */
    #[\PHPUnit\Framework\Attributes\DataProvider('pages')]
    public function test_action_buttons_never_wrap_onto_a_second_line(string $url): void
    {
        if (str_starts_with($url, '/reports')) {
            $this->markTestSkipped('Printouts carry no action buttons.');
        }

        $html = $this->actingAs($this->admin)->get($url)->assertOk()->getContent();

        preg_match_all('#<td class="text-end">(.*?)</td>#s', $html, $cells);

        $withButtons = array_filter($cells[1], fn ($cell) => str_contains($cell, '<button'));
        $this->assertNotEmpty($withButtons, "No action cell found on {$url}");

        foreach ($withButtons as $cell) {
            $this->assertStringContainsString(
                'd-flex gap-2 justify-content-end',
                $cell,
                "An ACTIONS cell on {$url} has no flex wrapper, so its buttons will wrap on a narrow column."
            );
        }
    }
}
