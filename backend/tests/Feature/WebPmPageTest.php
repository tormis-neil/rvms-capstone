<?php

namespace Tests\Feature;

use App\Models\Agency;
use App\Models\PmSchedule;
use App\Models\RepairLog;
use App\Models\User;
use App\Models\Vehicle;
use Illuminate\Http\UploadedFile;
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

    /**
     * The External Shop Name field, matching Repair Logs. PM completion recorded
     * the source but never which outside shop did the work (2026-08).
     */
    public function test_the_complete_modal_offers_the_external_shop_name_field(): void
    {
        $html = $this->actingAs($this->admin)->get('/pm')->assertOk()->getContent();

        $this->assertStringContainsString('completion_external_shop_name', $html);
        // Hidden until External Repair Shop is chosen, the same way repairs does it.
        $this->assertStringContainsString('js-shop-wrap', $html);
        $this->assertStringContainsString('bindShopToggle', $html);
    }

    public function test_completing_through_the_page_stores_the_shop_name(): void
    {
        $schedule = PmSchedule::factory()->create([
            'agency_id' => $this->agency->id,
            'vehicle_id' => Vehicle::factory()->create(['agency_id' => $this->agency->id])->id,
        ]);

        $this->actingAs($this->admin)
            ->from('/pm')
            ->patch(route('pm.complete', $schedule), [
                'date_serviced' => now()->toDateString(),
                'completion_repair_source' => RepairLog::SOURCE_EXTERNAL,
                'completion_external_shop_name' => 'Calbayog Diesel Works',
                // External work also needs its receipt (FR-14, 2026-08).
                'completion_receipt' => UploadedFile::fake()->create('receipt.pdf', 120, 'application/pdf'),
            ])
            ->assertRedirect('/pm');

        $this->assertSame('Calbayog Diesel Works', $schedule->fresh()->completion_external_shop_name);
    }

    public function test_the_page_refuses_an_external_completion_with_no_shop_name(): void
    {
        $schedule = PmSchedule::factory()->create([
            'agency_id' => $this->agency->id,
            'vehicle_id' => Vehicle::factory()->create(['agency_id' => $this->agency->id])->id,
        ]);

        $this->actingAs($this->admin)
            ->from('/pm')
            ->patch(route('pm.complete', $schedule), [
                'date_serviced' => now()->toDateString(),
                'completion_repair_source' => RepairLog::SOURCE_EXTERNAL,
            ])
            ->assertSessionHasErrors('completion_external_shop_name');
    }
}
