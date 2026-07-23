<?php

namespace Tests\Feature;

use App\Models\Agency;
use App\Models\PmSchedule;
use App\Models\RepairLog;
use App\Models\User;
use App\Models\Vehicle;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * R5 Day 10 — the Preventive Maintenance dashboard page (Blade twin of
 * /api/v1/pm-schedules).
 */
class WebPmPageTest extends TestCase
{
    use RefreshDatabase;

    private Agency $agency;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->agency = Agency::factory()->create(['code' => 'BFP']);
        $this->admin = User::factory()->admin()->create(['agency_id' => $this->agency->id]);
    }

    public function test_page_shows_active_and_completed_schedules_scoped_to_agency(): void
    {
        $vehicle = Vehicle::factory()->create(['agency_id' => $this->agency->id]);
        PmSchedule::factory()->create([
            'agency_id' => $this->agency->id, 'vehicle_id' => $vehicle->id,
            'service_target' => 'Oil Change & Filter',
        ]);

        $other = Agency::factory()->create(['code' => 'PNP']);
        PmSchedule::factory()->create([
            'agency_id' => $other->id,
            'vehicle_id' => Vehicle::factory()->create(['agency_id' => $other->id])->id,
            'service_target' => 'Secret PNP service',
        ]);

        $this->actingAs($this->admin)
            ->get('/pm')
            ->assertOk()
            ->assertSee('Preventive Maintenance')
            ->assertSee('Oil Change & Filter')
            ->assertDontSee('Secret PNP service');
    }

    public function test_admin_creates_a_mileage_schedule_from_the_page(): void
    {
        $vehicle = Vehicle::factory()->create(['agency_id' => $this->agency->id, 'current_mileage' => 44000]);

        $this->actingAs($this->admin)
            ->from('/pm')
            ->post('/pm', [
                'vehicle_id' => $vehicle->id,
                'service_target' => 'Oil Change',
                'pm_type' => PmSchedule::TYPE_MILEAGE,
                'interval_km' => 5000,
                'last_pm_mileage' => 40000,
                'due_soon_threshold_km' => 500,
            ])
            ->assertRedirect(route('pm'));

        $this->assertDatabaseHas('pm_schedules', [
            'vehicle_id' => $vehicle->id,
            'due_mileage' => 45000,
            'status' => PmSchedule::STATUS_UPCOMING,
        ]);
    }

    public function test_mark_completed_from_the_page(): void
    {
        $vehicle = Vehicle::factory()->create(['agency_id' => $this->agency->id]);
        $pm = PmSchedule::factory()->create(['agency_id' => $this->agency->id, 'vehicle_id' => $vehicle->id]);

        $this->actingAs($this->admin)
            ->from('/pm')
            ->patch("/pm/{$pm->id}/complete", [
                'date_serviced' => now()->toDateString(),
                'completion_repair_source' => RepairLog::SOURCE_INTERNAL,
            ])
            ->assertRedirect(route('pm'));

        $this->assertSame(PmSchedule::STATUS_COMPLETED, $pm->fresh()->status);
    }

    public function test_guest_is_redirected_to_login(): void
    {
        $this->get('/pm')->assertRedirect(route('login'));
    }
}
