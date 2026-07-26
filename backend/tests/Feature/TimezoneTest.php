<?php

namespace Tests\Feature;

use App\Models\Agency;
use App\Models\DamageReport;
use App\Models\Inspection;
use App\Models\User;
use App\Models\Vehicle;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

/**
 * The four agencies operate in Philippine time. Under the previous UTC default,
 * anything recorded before 08:00 Manila was stored and displayed on the PREVIOUS
 * day — which broke the "daily" premise of FR-09 inspections (a 07:30 AM
 * submission showed as "Yesterday") and shifted damage report dates.
 *
 * These tests pin that behaviour so the timezone cannot silently regress.
 */
class TimezoneTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_application_runs_in_manila_time(): void
    {
        $this->assertSame('Asia/Manila', config('app.timezone'));
    }

    public function test_an_early_morning_record_is_dated_today_not_yesterday(): void
    {
        // 07:30 AM Manila == 23:30 the PREVIOUS day in UTC — the exact case that
        // used to mislabel a daily inspection.
        $morning = Carbon::today()->setTime(7, 30);

        $agency = Agency::factory()->create(['code' => 'BFP']);
        $inspection = Inspection::factory()->create([
            'agency_id' => $agency->id,
            'vehicle_id' => Vehicle::factory()->create(['agency_id' => $agency->id])->id,
            'driver_id' => User::factory()->driver()->create(['agency_id' => $agency->id])->id,
            'inspection_date' => $morning->toDateString(),
            'created_at' => $morning,
        ]);

        $this->assertSame('Today', $inspection->dateLabel());
        $this->assertSame('07:30 AM', $inspection->timeLabel());
        $this->assertSame(Carbon::today()->toDateString(), $inspection->inspection_date->toDateString());
    }

    public function test_a_damage_report_filed_in_the_morning_carries_todays_date(): void
    {
        $agency = Agency::factory()->create(['code' => 'BFP']);
        $report = DamageReport::factory()->create([
            'agency_id' => $agency->id,
            'vehicle_id' => Vehicle::factory()->create(['agency_id' => $agency->id])->id,
            'driver_id' => User::factory()->driver()->create(['agency_id' => $agency->id])->id,
            'date_reported' => Carbon::today()->toDateString(),
            'created_at' => Carbon::today()->setTime(6, 15),
        ]);

        $this->assertSame('Today', $report->dateLabel());
        $this->assertSame(Carbon::today()->toDateString(), $report->date_reported->toDateString());
    }
}
