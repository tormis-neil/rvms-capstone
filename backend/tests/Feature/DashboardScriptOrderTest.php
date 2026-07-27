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
 * Guards the browser-side wiring of the shared vehicle-status control
 * (design decision 9), which the rest of the suite structurally cannot see:
 * every other test drives the server over HTTP and never executes page
 * JavaScript.
 *
 * Two real defects motivated this. Both partials were included inside
 * @section('modals'), which the layout emitted BEFORE the Bootstrap bundle, so
 * partials/status-confirm's `new bootstrap.Modal(el)` threw at parse time and
 * window.confirmStatusChange was never defined — every status change on
 * Vehicles, Repairs, PM, Inspections and Damage failed silently. Separately,
 * inspection and damage rows carried no data-vehicle-id, so the shared modal
 * built its action as /vehicles/undefined/status.
 *
 * Both are visible in the rendered HTML, so both are checkable here.
 */
class DashboardScriptOrderTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(InspectionChecklistSeeder::class);

        $agency = Agency::factory()->create(['code' => 'BFP']);
        $this->admin = User::factory()->admin()->create(['agency_id' => $agency->id]);
        $driver = User::factory()->driver()->create(['agency_id' => $agency->id]);
        $vehicle = Vehicle::factory()->create([
            'agency_id' => $agency->id,
            'assigned_driver_id' => $driver->id,
        ]);

        // REVIEWED rows specifically: those are the ones that render the shared
        // status button, so a pending-only fixture would not exercise it.
        $inspection = Inspection::factory()->create([
            'agency_id' => $agency->id,
            'vehicle_id' => $vehicle->id,
            'driver_id' => $driver->id,
            'review_status' => Inspection::STATUS_REVIEWED,
            'reviewed_by' => $this->admin->id,
            'reviewed_at' => now(),
        ]);
        foreach (InspectionChecklistItem::forAgencyCode('BFP')->get() as $item) {
            $inspection->items()->create(['checklist_item_id' => $item->id, 'status' => 'OK']);
        }

        DamageReport::factory()->create([
            'agency_id' => $agency->id,
            'vehicle_id' => $vehicle->id,
            'driver_id' => $driver->id,
            'status' => DamageReport::STATUS_REVIEWED,
            'reviewed_by' => $this->admin->id,
            'reviewed_at' => now(),
        ]);
        RepairLog::factory()->create(['agency_id' => $agency->id, 'vehicle_id' => $vehicle->id]);
        PmSchedule::factory()->create(['agency_id' => $agency->id, 'vehicle_id' => $vehicle->id]);
        Dispatch::factory()->create([
            'agency_id' => $agency->id,
            'vehicle_id' => $vehicle->id,
            'driver_id' => $driver->id,
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
        ];
    }

    /**
     * Nothing may reference `bootstrap` before the bundle that defines it.
     */
    #[\PHPUnit\Framework\Attributes\DataProvider('pages')]
    public function test_bootstrap_loads_before_any_script_that_uses_it(string $url): void
    {
        $html = $this->actingAs($this->admin)->get($url)->assertOk()->getContent();

        $bundleAt = strpos($html, 'bootstrap.bundle.min.js');
        $this->assertNotFalse($bundleAt, "No Bootstrap bundle on {$url}");

        preg_match_all('#<script(?![^>]*\ssrc=)[^>]*>(.*?)</script>#s', $html, $inline, PREG_OFFSET_CAPTURE);

        foreach ($inline[0] as $index => [$tag, $offset]) {
            $body = $inline[1][$index][0];

            if (! str_contains($body, 'bootstrap.') && ! str_contains($body, 'confirmStatusChange')) {
                continue;
            }

            $this->assertGreaterThan(
                $bundleAt,
                $offset,
                "An inline script on {$url} references Bootstrap or confirmStatusChange at byte {$offset}, "
                ."before the bundle loads at byte {$bundleAt}. It will throw and never define its functions."
            );
        }
    }

    /**
     * Any page offering the shared status control must also define it.
     */
    #[\PHPUnit\Framework\Attributes\DataProvider('pages')]
    public function test_pages_with_a_status_control_define_the_confirmation(string $url): void
    {
        $html = $this->actingAs($this->admin)->get($url)->assertOk()->getContent();

        if (! str_contains($html, 'confirmStatusChange(')) {
            $this->markTestSkipped("{$url} has no vehicle-status control.");
        }

        $this->assertStringContainsString(
            'window.confirmStatusChange =',
            $html,
            "{$url} calls confirmStatusChange but never includes partials.status-confirm, "
            .'so every status change there fails silently.'
        );
    }

    /**
     * Reviewing writes the shared vehicle status, so it must pass through the same
     * confirmation as the circular-arrows button (design decision 9). The review
     * modals originally submitted straight to the server, so setting a vehicle
     * Not Operational from a review happened with no dialog while changing it
     * afterwards asked — the same write, confirmed inconsistently.
     */
    #[\PHPUnit\Framework\Attributes\DataProvider('pages')]
    public function test_every_form_writing_a_vehicle_status_is_bound_to_the_confirmation(string $url): void
    {
        $html = $this->actingAs($this->admin)->get($url)->assertOk()->getContent();

        $document = new \DOMDocument;
        libxml_use_internal_errors(true);
        $document->loadHTML($html);
        libxml_clear_errors();

        $selects = (new \DOMXPath($document))->query('//select[@name="vehicle_status"]');

        if ($selects->length === 0) {
            $this->markTestSkipped("{$url} has no review form that writes a vehicle status.");
        }

        foreach ($selects as $select) {
            $form = $select;
            while ($form !== null && $form->nodeName !== 'form') {
                $form = $form->parentNode;
            }

            $this->assertNotNull($form, "A vehicle_status select on {$url} is not inside a form.");

            $id = $form->getAttribute('id');
            $this->assertNotSame('', $id, "A status-writing form on {$url} has no id to bind to.");

            $this->assertStringContainsString(
                "bindReviewConfirmation('{$id}'",
                $html,
                "Form #{$id} on {$url} writes the vehicle status but is not bound to the "
                .'shared confirmation, so that change would commit with no dialog.'
            );
        }
    }

    /**
     * The shared modal builds its action from the row's data-vehicle-id.
     */
    #[\PHPUnit\Framework\Attributes\DataProvider('pages')]
    public function test_status_buttons_sit_on_rows_carrying_a_vehicle_id(string $url): void
    {
        $html = $this->actingAs($this->admin)->get($url)->assertOk()->getContent();

        $document = new \DOMDocument;
        libxml_use_internal_errors(true);
        $document->loadHTML($html);
        libxml_clear_errors();

        $buttons = (new \DOMXPath($document))
            ->query('//button[contains(concat(" ", normalize-space(@class), " "), " js-status ")]');

        if ($buttons->length === 0) {
            $this->markTestSkipped("{$url} has no shared status button.");
        }

        foreach ($buttons as $button) {
            $row = $button;
            while ($row !== null && $row->nodeName !== 'tr') {
                $row = $row->parentNode;
            }

            $this->assertNotNull($row, "A status button on {$url} is not inside a table row.");
            $this->assertNotSame(
                '',
                $row->getAttribute('data-vehicle-id'),
                "A row with a status button on {$url} carries no data-vehicle-id, "
                .'so the modal would submit to /vehicles/undefined/status.'
            );
        }
    }
}
