<?php

namespace Tests\Feature;

use App\Models\Agency;
use App\Models\PmSchedule;
use App\Models\RepairLog;
use App\Models\User;
use App\Models\Vehicle;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/**
 * The odometer at the time of service (FR-14, 2026-08).
 *
 * Completing a mileage-based schedule recorded the date, source, document,
 * parts and remarks — but not the mileage. So the NEXT cycle's "Last PM
 * Mileage" had to be typed from the vehicle's CURRENT reading, which has moved
 * on since the service, and every cycle's target drifted later than the last.
 * The drift accumulates: a truck serviced at 45,000 km but recorded three weeks
 * later at 45,600 gets its next oil change set 600 km late, then 1,200, and so
 * on until somebody notices the schedule no longer matches the manual.
 */
class PmCompletionMileageTest extends TestCase
{
    use RefreshDatabase;

    private Agency $agency;

    private User $admin;

    private Vehicle $vehicle;

    protected function setUp(): void
    {
        parent::setUp();

        $this->agency = Agency::factory()->create(['code' => 'BFP']);
        $this->admin = User::factory()->admin()->create(['agency_id' => $this->agency->id]);
        $this->vehicle = Vehicle::factory()->create([
            'agency_id' => $this->agency->id,
            'plate_number' => 'BFP-0001',
            'current_mileage' => 45600,
        ]);
    }

    private function schedule(string $type = PmSchedule::TYPE_MILEAGE): PmSchedule
    {
        return PmSchedule::factory()->create([
            'agency_id' => $this->agency->id,
            'vehicle_id' => $this->vehicle->id,
            'service_target' => 'Oil Change & Filter',
            'pm_type' => $type,
            'interval_km' => $type === PmSchedule::TYPE_MILEAGE ? 5000 : null,
            'last_pm_mileage' => $type === PmSchedule::TYPE_MILEAGE ? 40000 : null,
            'due_mileage' => $type === PmSchedule::TYPE_MILEAGE ? 45000 : null,
            'due_date' => $type === PmSchedule::TYPE_TIME ? now()->toDateString() : null,
            'status' => PmSchedule::STATUS_DUE,
        ]);
    }

    private function complete(PmSchedule $schedule, array $extra = []): \Illuminate\Testing\TestResponse
    {
        Sanctum::actingAs($this->admin);

        return $this->patchJson("/api/v1/pm-schedules/{$schedule->id}/complete", array_merge([
            'date_serviced' => now()->toDateString(),
            'completion_repair_source' => RepairLog::SOURCE_GSO,
        ], $extra));
    }

    public function test_the_odometer_at_service_is_recorded(): void
    {
        $schedule = $this->schedule();

        // Serviced at 45,000 — not the 45,600 the vehicle reads today.
        $this->complete($schedule, ['completion_mileage' => 45000])->assertOk();

        $this->assertSame(45000, $schedule->fresh()->completion_mileage);
    }

    /**
     * The drift this exists to prevent: the figure recorded is the one from the
     * DAY OF SERVICE, and it stays put even though the vehicle keeps running.
     */
    public function test_the_recorded_figure_does_not_follow_the_vehicle(): void
    {
        $schedule = $this->schedule();

        $this->complete($schedule, ['completion_mileage' => 45000])->assertOk();

        // The truck keeps working after the service.
        $this->vehicle->update(['current_mileage' => 47800]);

        $this->assertSame(45000, $schedule->fresh()->completion_mileage,
            'The recorded service mileage moved with the vehicle — the next cycle would drift.');
    }

    /** Optional: a time-based schedule has no odometer to record. */
    public function test_a_time_based_completion_needs_no_mileage(): void
    {
        $schedule = $this->schedule(PmSchedule::TYPE_TIME);

        $this->complete($schedule)->assertOk();

        $this->assertSame(PmSchedule::STATUS_COMPLETED, $schedule->fresh()->status);
        $this->assertNull($schedule->fresh()->completion_mileage);
    }

    /** And a mileage-based one still completes if the reading is not to hand. */
    public function test_a_mileage_based_completion_without_the_reading_still_saves(): void
    {
        $schedule = $this->schedule();

        $this->complete($schedule)->assertOk();

        $this->assertSame(PmSchedule::STATUS_COMPLETED, $schedule->fresh()->status);
        $this->assertNull($schedule->fresh()->completion_mileage);
    }

    public function test_a_negative_reading_is_refused(): void
    {
        $schedule = $this->schedule();

        $this->complete($schedule, ['completion_mileage' => -5])
            ->assertStatus(422)
            ->assertJsonValidationErrors('completion_mileage');

        $this->assertNotSame(PmSchedule::STATUS_COMPLETED, $schedule->fresh()->status);
    }

    /* ------------------------------- the page ----------------------------- */

    public function test_the_completion_form_offers_the_field_prefilled_from_the_vehicle(): void
    {
        $this->schedule();

        $html = $this->actingAs($this->admin)->get('/pm')->assertOk()->getContent();

        $this->assertStringContainsString('name="completion_mileage"', $html);
        $this->assertStringContainsString('Odometer at Service', $html);
        // The row carries the current reading for the modal to prefill with.
        $this->assertStringContainsString('data-current-mileage="45600"', $html);
        // Hidden until a mileage-based schedule is chosen.
        $this->assertStringContainsString('js-completion-mileage', $html);
    }

    public function test_the_web_completion_stores_it_too(): void
    {
        $schedule = $this->schedule();

        $this->actingAs($this->admin)
            ->from('/pm')
            ->patch("/pm/{$schedule->id}/complete", [
                'date_serviced' => now()->toDateString(),
                'completion_repair_source' => RepairLog::SOURCE_GSO,
                'completion_mileage' => 45000,
            ])
            ->assertRedirect();

        $this->assertSame(45000, $schedule->fresh()->completion_mileage);
    }

    /** Recorded and then shown — a value nothing displays is half a feature. */
    public function test_the_completed_row_shows_the_reading(): void
    {
        $schedule = $this->schedule();
        $this->complete($schedule, ['completion_mileage' => 45000])->assertOk();

        $this->actingAs($this->admin)->get('/pm')
            ->assertOk()
            ->assertSee('45,000 km');
    }

    /** And the printed PM record carries it, like every other completion field. */
    public function test_the_pm_report_carries_the_reading(): void
    {
        $schedule = $this->schedule();
        $this->complete($schedule, ['completion_mileage' => 45000])->assertOk();

        $report = app(\App\Services\ReportBuilder::class)->build('pm', $this->agency->id);

        $this->assertContains('Odometer at Service', $report['columns']);
        $this->assertContains('45,000 km', $report['rows'][0]);
    }
}
