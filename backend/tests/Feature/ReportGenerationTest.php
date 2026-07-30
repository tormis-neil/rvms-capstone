<?php

namespace Tests\Feature;

use App\Models\Agency;
use App\Models\DamageReport;
use App\Models\Dispatch;
use App\Models\Inspection;
use App\Models\PmSchedule;
use App\Models\RepairLog;
use App\Models\User;
use App\Models\Vehicle;
use App\Services\ReportBuilder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * R8 Day 15 — the six printable reports (FR-20).
 *
 * A report is the one artefact of this system that leaves it: it gets printed,
 * signed and filed. So the tests care about three things in particular — that
 * a report never contains another agency's records, that a filter narrows the
 * set rather than being quietly ignored, and that the printout carries the
 * administrator who produced it (FR-20 requires that, and the prototype's
 * header does not have it).
 */
class ReportGenerationTest extends TestCase
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
        $this->admin = User::factory()->admin()->create([
            'agency_id' => $this->agency->id, 'name' => 'Maria Santos',
        ]);
        $this->driver = User::factory()->driver()->create([
            'agency_id' => $this->agency->id, 'name' => 'Juan Dela Cruz',
        ]);
        $this->vehicle = Vehicle::factory()->create([
            'agency_id' => $this->agency->id, 'plate_number' => 'ABC-1234', 'type' => 'Fire Truck',
        ]);
    }

    /* ------------------------------- the API ------------------------------ */

    /** Every type must answer with a table, whether or not it holds rows. */
    public function test_all_six_report_types_respond_with_columns_and_rows(): void
    {
        foreach (array_keys(ReportBuilder::TYPES) as $type) {
            $this->actingAs($this->admin, 'sanctum')
                ->getJson("/api/v1/reports/{$type}")
                ->assertOk()
                ->assertJsonStructure(['data' => [
                    'type', 'title', 'columns', 'rows', 'filter_summary',
                    'record_count', 'generated_by', 'generated_at',
                ]])
                ->assertJsonPath('data.type', $type)
                ->assertJsonPath('data.title', ReportBuilder::TYPES[$type]);
        }
    }

    /** FR-20: a generated report records who produced it and when. */
    public function test_a_report_records_the_administrator_who_generated_it(): void
    {
        $this->actingAs($this->admin, 'sanctum')
            ->getJson('/api/v1/reports/vehicle-status')
            ->assertOk()
            ->assertJsonPath('data.generated_by', 'Maria Santos');
    }

    public function test_an_unknown_report_type_is_not_found(): void
    {
        $this->actingAs($this->admin, 'sanctum')
            ->getJson('/api/v1/reports/payroll')
            ->assertNotFound();
    }

    /**
     * A range outside the prototype's three presets is refused rather than
     * silently widened to All Dates, which would hand back more records than
     * the admin asked to print.
     */
    public function test_a_range_outside_the_presets_is_rejected(): void
    {
        $this->actingAs($this->admin, 'sanctum')
            ->getJson('/api/v1/reports/inspections?range=last-decade')
            ->assertStatus(422)
            ->assertJsonValidationErrors('range');
    }

    public function test_each_preset_range_is_accepted(): void
    {
        foreach (array_keys(ReportBuilder::RANGES) as $range) {
            $this->actingAs($this->admin, 'sanctum')
                ->getJson("/api/v1/reports/inspections?range={$range}")
                ->assertOk();
        }
    }

    public function test_a_driver_cannot_generate_reports(): void
    {
        $this->actingAs($this->driver, 'sanctum')
            ->getJson('/api/v1/reports/inspections')
            ->assertForbidden();
    }

    public function test_reports_require_authentication(): void
    {
        $this->getJson('/api/v1/reports/inspections')->assertUnauthorized();
    }

    /* ------------------------------ filtering ----------------------------- */

    public function test_the_vehicle_filter_narrows_the_report(): void
    {
        $other = Vehicle::factory()->create([
            'agency_id' => $this->agency->id, 'plate_number' => 'ZZZ-0001',
        ]);

        foreach ([$this->vehicle, $other] as $vehicle) {
            RepairLog::factory()->create([
                'agency_id' => $this->agency->id, 'vehicle_id' => $vehicle->id,
                'driver_id' => $this->driver->id, 'repair_date' => now()->subDay(),
            ]);
        }

        $unfiltered = $this->actingAs($this->admin, 'sanctum')
            ->getJson('/api/v1/reports/repairs-maintenance')->json('data.record_count');
        $filtered = $this->actingAs($this->admin, 'sanctum')
            ->getJson('/api/v1/reports/repairs-maintenance?vehicle_id='.$this->vehicle->id);

        $this->assertSame(2, $unfiltered);
        $filtered->assertOk()->assertJsonPath('data.record_count', 1);
        $this->assertStringContainsString('ABC-1234', json_encode($filtered->json('data.rows')));
        $this->assertStringNotContainsString('ZZZ-0001', json_encode($filtered->json('data.rows')));
    }

    public function test_the_date_range_narrows_the_report(): void
    {
        RepairLog::factory()->create([
            'agency_id' => $this->agency->id, 'vehicle_id' => $this->vehicle->id,
            'driver_id' => $this->driver->id, 'repair_date' => now()->subDay(),
        ]);
        RepairLog::factory()->create([
            'agency_id' => $this->agency->id, 'vehicle_id' => $this->vehicle->id,
            'driver_id' => $this->driver->id, 'repair_date' => now()->subMonths(6),
        ]);

        $this->actingAs($this->admin, 'sanctum')
            ->getJson('/api/v1/reports/repairs-maintenance?range=all')
            ->assertJsonPath('data.record_count', 2);

        $this->actingAs($this->admin, 'sanctum')
            ->getJson('/api/v1/reports/repairs-maintenance?range=last-7-days')
            ->assertJsonPath('data.record_count', 1);
    }

    /**
     * A filter naming another agency's record fails validation rather than
     * returning an empty report — an empty report would confirm the id exists
     * somewhere, which is the FR-02 leak a validation error avoids.
     */
    public function test_filtering_by_another_agencys_vehicle_is_refused(): void
    {
        $other = Agency::factory()->create(['code' => 'CHO']);
        $theirVehicle = Vehicle::factory()->create(['agency_id' => $other->id]);

        $this->actingAs($this->admin, 'sanctum')
            ->getJson('/api/v1/reports/inspections?vehicle_id='.$theirVehicle->id)
            ->assertStatus(422)
            ->assertJsonValidationErrors('vehicle_id');
    }

    /** The snapshot report takes no filters, so it is never narrowed. */
    public function test_the_vehicle_status_summary_is_an_unfiltered_snapshot(): void
    {
        Vehicle::factory()->count(2)->create(['agency_id' => $this->agency->id]);

        $response = $this->actingAs($this->admin, 'sanctum')
            ->getJson('/api/v1/reports/vehicle-status?vehicle_id='.$this->vehicle->id)
            ->assertOk();

        $this->assertSame(3, $response->json('data.record_count'));
        $this->assertSame(['Current snapshot of all vehicles'], $response->json('data.filter_summary'));
    }

    /* --------------------------- the agency wall -------------------------- */

    public function test_no_report_contains_another_agencys_records(): void
    {
        $other = Agency::factory()->create(['code' => 'CHO']);
        $theirDriver = User::factory()->driver()->create([
            'agency_id' => $other->id, 'name' => 'Secret CHO Driver',
        ]);
        $theirVehicle = Vehicle::factory()->create([
            'agency_id' => $other->id, 'plate_number' => 'CHO-9999',
        ]);

        Inspection::factory()->create([
            'agency_id' => $other->id, 'vehicle_id' => $theirVehicle->id, 'driver_id' => $theirDriver->id,
        ]);
        DamageReport::factory()->create([
            'agency_id' => $other->id, 'vehicle_id' => $theirVehicle->id, 'driver_id' => $theirDriver->id,
        ]);
        RepairLog::factory()->create([
            'agency_id' => $other->id, 'vehicle_id' => $theirVehicle->id, 'driver_id' => $theirDriver->id,
        ]);
        PmSchedule::factory()->create([
            'agency_id' => $other->id, 'vehicle_id' => $theirVehicle->id,
        ]);
        Dispatch::factory()->create([
            'agency_id' => $other->id, 'vehicle_id' => $theirVehicle->id, 'driver_id' => $theirDriver->id,
        ]);

        foreach (array_keys(ReportBuilder::TYPES) as $type) {
            $body = json_encode(
                $this->actingAs($this->admin, 'sanctum')->getJson("/api/v1/reports/{$type}")->json('data.rows')
            );

            $this->assertStringNotContainsString('CHO-9999', $body, "{$type} leaked a vehicle");
            $this->assertStringNotContainsString('Secret CHO Driver', $body, "{$type} leaked a driver");
        }
    }

    /* ------------------------------ the page ------------------------------ */

    public function test_the_page_lists_the_six_report_types(): void
    {
        $response = $this->actingAs($this->admin)->get('/reports')->assertOk();

        foreach (ReportBuilder::TYPES as $slug => $title) {
            $response->assertSee($title, false);
            $response->assertSee('data-report-slug="'.$slug.'"', false);
        }
    }

    /** Without a ?type= the page is just the six cards — no report yet. */
    public function test_the_page_renders_no_report_until_one_is_requested(): void
    {
        $this->actingAs($this->admin)->get('/reports')->assertOk()
            ->assertDontSee('reportPrintArea', false);
    }

    public function test_generating_a_report_renders_the_print_area_with_the_stamp(): void
    {
        RepairLog::factory()->create([
            'agency_id' => $this->agency->id, 'vehicle_id' => $this->vehicle->id,
            'driver_id' => $this->driver->id, 'scope_of_work' => 'Front brake pad replacement',
        ]);

        $html = $this->actingAs($this->admin)
            ->get('/reports?type=repairs-maintenance')
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString('reportPrintArea', $html);
        $this->assertStringContainsString('Repair &amp; Maintenance History', $html);
        $this->assertStringContainsString('Front brake pad replacement', $html);
        // FR-20: who generated it, and when.
        $this->assertStringContainsString('Generated by: Maria Santos', $html);
        $this->assertStringContainsString('Generated: '.now()->format('F j, Y'), $html);
        // The prototype's print/clear controls, excluded from the printout.
        $this->assertStringContainsString('no-print', $html);
    }

    public function test_a_report_with_no_matching_records_says_so(): void
    {
        $this->actingAs($this->admin)
            ->get('/reports?type=inspections&range=last-7-days')
            ->assertOk()
            ->assertSee('No records match the selected filters.');
    }

    public function test_the_page_refuses_a_bad_range(): void
    {
        $this->actingAs($this->admin)
            ->get('/reports?type=inspections&range=whenever')
            ->assertSessionHasErrors('range');
    }

    /** An unknown type falls back to the cards rather than erroring. */
    public function test_an_unknown_type_on_the_page_renders_no_report(): void
    {
        $this->actingAs($this->admin)->get('/reports?type=payroll')->assertOk()
            ->assertDontSee('reportPrintArea', false);
    }

    public function test_a_guest_cannot_reach_the_reports_page(): void
    {
        $this->get('/reports')->assertRedirect(route('login'));
    }

    /**
     * 403, not a redirect: a driver cannot sign in on the web at all, so
     * reaching here with a driver session means the role gate is the last
     * line — and a wrong role is forbidden, not unauthenticated.
     */
    public function test_a_driver_cannot_reach_the_reports_page(): void
    {
        $this->actingAs($this->driver)->get('/reports')->assertForbidden();
    }
}
