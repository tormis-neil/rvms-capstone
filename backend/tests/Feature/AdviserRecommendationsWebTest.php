<?php

namespace Tests\Feature;

use App\Models\Agency;
use App\Models\Dispatch;
use App\Models\PmSchedule;
use App\Models\User;
use App\Models\Vehicle;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Web (dashboard) side of the 2026-09 adviser changes: the Blade pages render
 * the new fields, the password reveal toggle is present (F6, NFR-03), and the
 * web store routes persist the new columns — the surface the agencies actually
 * use.
 */
class AdviserRecommendationsWebTest extends TestCase
{
    use RefreshDatabase;

    private Agency $agency;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');

        $this->agency = Agency::factory()->create(['code' => 'CHO']);
        $this->admin = User::factory()->admin()->create(['agency_id' => $this->agency->id]);
    }

    /* ---- F6: password reveal toggle renders on every page with a password ---- */

    public function test_the_password_reveal_toggle_renders_on_profile_and_drivers(): void
    {
        $this->actingAs($this->admin);

        $this->get('/profile')->assertOk()->assertSee('data-password-toggle', false);
        $this->get('/drivers')->assertOk()->assertSee('data-password-toggle', false);
    }

    /* ---- F4: NC II on the drivers page + web store ---- */

    public function test_the_drivers_page_shows_the_nc_ii_fields_and_flag(): void
    {
        User::factory()->driver()->create([
            'agency_id' => $this->agency->id,
            'name' => 'Expiring Cert Driver',
            'nc_ii_expiry_date' => now()->subDay()->toDateString(),
        ]);

        $this->actingAs($this->admin)
            ->get('/drivers')
            ->assertOk()
            ->assertSee('NC II Number')
            ->assertSee('NC II Expired');
    }

    public function test_a_driver_web_store_persists_nc_ii(): void
    {
        $this->actingAs($this->admin)
            ->post('/drivers', [
                'name' => 'Web Driver',
                'email' => 'webdriver@example.com',
                'password' => 'secret123',
                'password_confirmation' => 'secret123',
                'nc_ii_number' => 'NCII-WEB-1',
                'nc_ii_expiry_date' => now()->addYear()->toDateString(),
            ])->assertRedirect(route('drivers'));

        $this->assertSame(
            'NCII-WEB-1',
            User::query()->where('email', 'webdriver@example.com')->firstOrFail()->nc_ii_number
        );
    }

    /* ---- F5: secondary driver on the vehicles page + web store ---- */

    public function test_the_vehicles_page_shows_the_secondary_driver(): void
    {
        $primary = User::factory()->driver()->create(['agency_id' => $this->agency->id, 'name' => 'Prime Mover']);
        $secondary = User::factory()->driver()->create(['agency_id' => $this->agency->id, 'name' => 'Backup Crew']);

        Vehicle::factory()->create([
            'agency_id' => $this->agency->id,
            'plate_number' => 'CREW-01',
            'assigned_driver_id' => $primary->id,
            'secondary_driver_id' => $secondary->id,
        ]);

        $this->actingAs($this->admin)
            ->get('/vehicles')
            ->assertOk()
            ->assertSee('Secondary Driver')
            ->assertSee('Backup Crew');
    }

    public function test_a_vehicle_web_store_persists_the_secondary_driver(): void
    {
        $primary = User::factory()->driver()->create(['agency_id' => $this->agency->id]);
        $secondary = User::factory()->driver()->create(['agency_id' => $this->agency->id]);

        $this->actingAs($this->admin)
            ->post('/vehicles', [
                'type' => 'Ambulance',
                'plate_number' => 'WEB-CREW',
                'make' => 'Toyota',
                'model' => 'HiAce',
                'current_mileage' => 500,
                'assigned_driver_id' => $primary->id,
                'secondary_driver_id' => $secondary->id,
            ])->assertRedirect(route('vehicles'));

        $this->assertSame(
            $secondary->id,
            Vehicle::query()->where('plate_number', 'WEB-CREW')->firstOrFail()->secondary_driver_id
        );
    }

    /* ---- F2: PM document via web store ---- */

    public function test_a_pm_web_store_persists_the_supporting_document(): void
    {
        $vehicle = Vehicle::factory()->create(['agency_id' => $this->agency->id, 'current_mileage' => 10_000]);

        $this->actingAs($this->admin)
            ->post('/pm', [
                'vehicle_id' => $vehicle->id,
                'service_target' => 'Brake Service',
                'pm_type' => PmSchedule::TYPE_MILEAGE,
                'interval_km' => 5_000,
                'last_pm_mileage' => 10_000,
                'due_soon_threshold_km' => 500,
                'schedule_document' => UploadedFile::fake()->create('checklist.pdf', 100, 'application/pdf'),
            ])->assertRedirect(route('pm'));

        $schedule = PmSchedule::query()->firstOrFail();
        $this->assertNotNull($schedule->schedule_document_path);
        Storage::disk('public')->assertExists($schedule->schedule_document_path);
    }

    /* ---- F3: travel order via web store ---- */

    public function test_a_dispatch_web_store_persists_the_travel_order(): void
    {
        $vehicle = Vehicle::factory()->create([
            'agency_id' => $this->agency->id,
            'status' => Vehicle::STATUS_OPERATIONAL,
        ]);
        $driver = User::factory()->driver()->create(['agency_id' => $this->agency->id]);

        $this->actingAs($this->admin)
            ->post('/dispatch', [
                'vehicle_id' => $vehicle->id,
                'driver_id' => $driver->id,
                'mission_type' => 'Patrol',
                'location' => 'City Proper',
                'time_out' => now()->format('Y-m-d H:i:s'),
                'travel_order' => UploadedFile::fake()->create('order.pdf', 100, 'application/pdf'),
            ])->assertRedirect(route('dispatch'));

        $this->assertNotNull(Dispatch::query()->firstOrFail()->travel_order_path);
    }
}
