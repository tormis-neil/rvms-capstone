<?php

namespace Tests\Feature;

use App\Models\Agency;
use App\Models\RepairLog;
use App\Models\User;
use App\Models\Vehicle;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * R4 Day 8 — the Repair Logs dashboard page (Blade twin of /api/v1/repairs).
 */
class WebRepairPageTest extends TestCase
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

    public function test_page_lists_only_own_agency_repairs(): void
    {
        $vehicle = Vehicle::factory()->create(['agency_id' => $this->agency->id, 'plate_number' => 'BFP-0001']);
        RepairLog::factory()->create([
            'agency_id' => $this->agency->id,
            'vehicle_id' => $vehicle->id,
            'scope_of_work' => 'Front brake pad replacement',
        ]);

        // Other agency's repair must not appear.
        $other = Agency::factory()->create(['code' => 'PNP']);
        RepairLog::factory()->create([
            'agency_id' => $other->id,
            'vehicle_id' => Vehicle::factory()->create(['agency_id' => $other->id, 'plate_number' => 'PNP-9999'])->id,
            'scope_of_work' => 'Secret PNP repair',
        ]);

        $this->actingAs($this->admin)
            ->get('/repairs')
            ->assertOk()
            ->assertSee('Repair Logging')
            ->assertSee('Front brake pad replacement')
            ->assertDontSee('Secret PNP repair');
    }

    public function test_admin_logs_a_repair_from_the_page(): void
    {
        $driver = User::factory()->driver()->create(['agency_id' => $this->agency->id]);
        $vehicle = Vehicle::factory()->create([
            'agency_id' => $this->agency->id,
            'assigned_driver_id' => $driver->id,
        ]);

        $this->actingAs($this->admin)
            ->from('/repairs')
            ->post('/repairs', [
                'vehicle_id' => $vehicle->id,
                'repair_date' => now()->toDateString(),
                'scope_of_work' => 'Oil change',
                'repair_source' => RepairLog::SOURCE_INTERNAL,
            ])
            ->assertRedirect(route('repairs'));

        // Driver is auto-derived from the vehicle record.
        $this->assertDatabaseHas('repair_logs', [
            'vehicle_id' => $vehicle->id,
            'driver_id' => $driver->id,
            'scope_of_work' => 'Oil change',
            'agency_id' => $this->agency->id,
        ]);
    }

    public function test_external_source_without_shop_name_is_rejected(): void
    {
        $vehicle = Vehicle::factory()->create(['agency_id' => $this->agency->id]);

        $this->actingAs($this->admin)
            ->from('/repairs')
            ->post('/repairs', [
                'vehicle_id' => $vehicle->id,
                'repair_date' => now()->toDateString(),
                'scope_of_work' => 'Engine work',
                'repair_source' => RepairLog::SOURCE_EXTERNAL,
            ])
            ->assertSessionHasErrors('external_shop_name');
    }

    public function test_guest_is_redirected_to_login(): void
    {
        $this->get('/repairs')->assertRedirect(route('login'));
    }
}
