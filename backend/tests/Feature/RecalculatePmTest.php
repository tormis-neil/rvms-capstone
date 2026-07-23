<?php

namespace Tests\Feature;

use App\Models\Agency;
use App\Models\PmSchedule;
use App\Models\Vehicle;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * R5 — rvms:recalculate-pm flips active schedules to Upcoming/Due Soon/Due at
 * the configured thresholds (FR-14, plan §6.7); Completed schedules are left
 * alone.
 */
class RecalculatePmTest extends TestCase
{
    use RefreshDatabase;

    private function mileageSchedule(int $currentMileage, int $due = 45000, int $threshold = 500, string $status = PmSchedule::STATUS_UPCOMING): PmSchedule
    {
        $agency = Agency::factory()->create();
        $vehicle = Vehicle::factory()->create(['agency_id' => $agency->id, 'current_mileage' => $currentMileage]);

        return PmSchedule::factory()->create([
            'agency_id' => $agency->id,
            'vehicle_id' => $vehicle->id,
            'pm_type' => PmSchedule::TYPE_MILEAGE,
            'interval_km' => 5000,
            'last_pm_mileage' => $due - 5000,
            'due_mileage' => $due,
            'due_soon_threshold_km' => $threshold,
            'status' => $status,
        ]);
    }

    public function test_mileage_far_from_due_is_upcoming(): void
    {
        $s = $this->mileageSchedule(currentMileage: 44000); // 44000 < 45000-500
        $this->artisan('rvms:recalculate-pm')->assertSuccessful();
        $this->assertSame(PmSchedule::STATUS_UPCOMING, $s->fresh()->status);
    }

    public function test_mileage_at_the_due_soon_boundary_is_due_soon(): void
    {
        $s = $this->mileageSchedule(currentMileage: 44500); // exactly 45000-500
        $this->artisan('rvms:recalculate-pm')->assertSuccessful();
        $this->assertSame(PmSchedule::STATUS_DUE_SOON, $s->fresh()->status);
    }

    public function test_mileage_at_or_past_due_is_due(): void
    {
        $s = $this->mileageSchedule(currentMileage: 45000);
        $this->artisan('rvms:recalculate-pm')->assertSuccessful();
        $this->assertSame(PmSchedule::STATUS_DUE, $s->fresh()->status);
    }

    public function test_time_based_boundaries(): void
    {
        $agency = Agency::factory()->create();
        $vehicle = Vehicle::factory()->create(['agency_id' => $agency->id]);

        $upcoming = PmSchedule::factory()->for($vehicle)->timeBased()->create([
            'agency_id' => $agency->id,
            'due_date' => now()->addDays(30)->toDateString(),
            'due_soon_threshold_days' => 7,
        ]);
        $dueSoon = PmSchedule::factory()->for($vehicle)->timeBased()->create([
            'agency_id' => $agency->id,
            'due_date' => now()->addDays(5)->toDateString(),
            'due_soon_threshold_days' => 7,
        ]);
        $due = PmSchedule::factory()->for($vehicle)->timeBased()->create([
            'agency_id' => $agency->id,
            'due_date' => now()->toDateString(),
            'due_soon_threshold_days' => 7,
        ]);

        $this->artisan('rvms:recalculate-pm')->assertSuccessful();

        $this->assertSame(PmSchedule::STATUS_UPCOMING, $upcoming->fresh()->status);
        $this->assertSame(PmSchedule::STATUS_DUE_SOON, $dueSoon->fresh()->status);
        $this->assertSame(PmSchedule::STATUS_DUE, $due->fresh()->status);
    }

    public function test_completed_schedules_are_left_untouched(): void
    {
        $s = $this->mileageSchedule(currentMileage: 50000, status: PmSchedule::STATUS_COMPLETED);
        $this->artisan('rvms:recalculate-pm')->assertSuccessful();
        $this->assertSame(PmSchedule::STATUS_COMPLETED, $s->fresh()->status);
    }
}
