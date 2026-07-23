<?php

namespace Tests\Feature;

use App\Models\Agency;
use App\Models\Dispatch;
use App\Models\User;
use App\Models\Vehicle;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * R6 — the Dispatch Logging dashboard page (Blade twin of /api/v1/dispatches).
 */
class WebDispatchPageTest extends TestCase
{
    use RefreshDatabase;

    private Agency $agency;

    private User $admin;

    private User $driver;

    protected function setUp(): void
    {
        parent::setUp();
        $this->agency = Agency::factory()->create(['code' => 'BFP']);
        $this->admin = User::factory()->admin()->create(['agency_id' => $this->agency->id]);
        $this->driver = User::factory()->driver()->create(['agency_id' => $this->agency->id]);
    }

    public function test_page_shows_active_count_and_own_agency_dispatches(): void
    {
        $vehicle = Vehicle::factory()->create(['agency_id' => $this->agency->id]);
        Dispatch::factory()->create([
            'agency_id' => $this->agency->id, 'vehicle_id' => $vehicle->id, 'driver_id' => $this->driver->id,
            'location' => 'Brgy. Obrero',
        ]);

        $other = Agency::factory()->create(['code' => 'PNP']);
        Dispatch::factory()->create([
            'agency_id' => $other->id,
            'vehicle_id' => Vehicle::factory()->create(['agency_id' => $other->id])->id,
            'driver_id' => User::factory()->driver()->create(['agency_id' => $other->id])->id,
            'location' => 'Secret PNP location',
        ]);

        $this->actingAs($this->admin)
            ->get('/dispatch')
            ->assertOk()
            ->assertSee('Dispatch Logging')
            ->assertSee('Brgy. Obrero')
            ->assertSee('1</strong> vehicle', false)
            ->assertDontSee('Secret PNP location');
    }

    public function test_open_dispatch_sets_the_vehicle_dispatched(): void
    {
        $vehicle = Vehicle::factory()->create(['agency_id' => $this->agency->id, 'status' => Vehicle::STATUS_OPERATIONAL]);

        $this->actingAs($this->admin)
            ->from('/dispatch')
            ->post('/dispatch', [
                'vehicle_id' => $vehicle->id,
                'driver_id' => $this->driver->id,
                'mission_type' => 'Patrol',
                'location' => 'Downtown',
                'time_out' => now()->toDateTimeString(),
            ])
            ->assertRedirect(route('dispatch'));

        $this->assertSame(Vehicle::STATUS_DISPATCHED, $vehicle->fresh()->status);
    }

    public function test_close_dispatch_writes_return_status_and_bumps_mileage(): void
    {
        $vehicle = Vehicle::factory()->create(['agency_id' => $this->agency->id, 'current_mileage' => 45000]);
        $dispatch = Dispatch::factory()->create([
            'agency_id' => $this->agency->id, 'vehicle_id' => $vehicle->id, 'driver_id' => $this->driver->id,
        ]);

        $this->actingAs($this->admin)
            ->from('/dispatch')
            ->patch("/dispatch/{$dispatch->id}/close", [
                'time_in' => now()->toDateTimeString(),
                'return_status' => Vehicle::STATUS_OPERATIONAL,
                'odometer_in' => 45300,
            ])
            ->assertRedirect(route('dispatch'));

        $this->assertSame(Vehicle::STATUS_OPERATIONAL, $vehicle->fresh()->status);
        $this->assertSame(45300, $vehicle->fresh()->current_mileage);
    }

    public function test_others_mission_without_detail_is_rejected(): void
    {
        $vehicle = Vehicle::factory()->create(['agency_id' => $this->agency->id]);

        $this->actingAs($this->admin)
            ->from('/dispatch')
            ->post('/dispatch', [
                'vehicle_id' => $vehicle->id,
                'driver_id' => $this->driver->id,
                'mission_type' => 'Others',
                'location' => 'Downtown',
                'time_out' => now()->toDateTimeString(),
            ])
            ->assertSessionHasErrors('mission_other');
    }

    public function test_guest_is_redirected_to_login(): void
    {
        $this->get('/dispatch')->assertRedirect(route('login'));
    }
}
