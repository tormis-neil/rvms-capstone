<?php

namespace Tests\Feature;

use App\Models\Agency;
use App\Models\User;
use App\Models\Vehicle;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * R2 — the /drivers dashboard page (Blade twin of the driver API), incl.
 * the Access Requests documented addition (FR-03).
 */
class WebDriverPageTest extends TestCase
{
    use RefreshDatabase;

    private Agency $agency;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->agency = Agency::factory()->create();
        $this->admin = User::factory()->admin()->create(['agency_id' => $this->agency->id]);
    }

    public function test_page_lists_only_own_agency_active_drivers(): void
    {
        User::factory()->driver()->create(['agency_id' => $this->agency->id, 'name' => 'Mine Driver']);
        User::factory()->driver()->create(['name' => 'Foreign Driver']); // other agency
        User::factory()->driver()->pending()->create(['agency_id' => $this->agency->id, 'name' => 'Pending Driver']);

        $response = $this->actingAs($this->admin)->get('/drivers');

        $response->assertOk()->assertSee('Mine Driver')->assertDontSee('Foreign Driver');
    }

    public function test_pending_driver_appears_in_access_requests_and_can_be_approved(): void
    {
        $pending = User::factory()->driver()->pending()->create(['agency_id' => $this->agency->id, 'name' => 'Applicant Name']);

        $this->actingAs($this->admin)
            ->get('/drivers')
            ->assertOk()
            ->assertSee('Access Requests')
            ->assertSee('Applicant Name');

        $this->patch("/drivers/{$pending->id}/approve")->assertRedirect(route('drivers'));

        $this->assertSame(User::STATUS_ACTIVE, $pending->fresh()->status);
    }

    public function test_pending_driver_can_be_rejected(): void
    {
        $pending = User::factory()->driver()->pending()->create(['agency_id' => $this->agency->id]);

        $this->actingAs($this->admin)
            ->patch("/drivers/{$pending->id}/reject")
            ->assertRedirect(route('drivers'));

        $this->assertSame(User::STATUS_REJECTED, $pending->fresh()->status);
    }

    public function test_store_creates_an_active_driver(): void
    {
        $this->actingAs($this->admin)->post('/drivers', [
            'name' => 'New Driver',
            'email' => 'new.driver@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ])->assertRedirect(route('drivers'));

        $driver = User::withoutGlobalScopes()->where('email', 'new.driver@example.com')->firstOrFail();
        $this->assertSame(User::STATUS_ACTIVE, $driver->status);
        $this->assertSame($this->agency->id, $driver->agency_id);
    }

    public function test_license_renewal_flow(): void
    {
        $driver = User::factory()->driver()->create([
            'agency_id' => $this->agency->id,
            'license_expiry_date' => now()->subDay(),
        ]);

        $newDate = now()->addYears(5)->toDateString();

        $this->actingAs($this->admin)
            ->patch("/drivers/{$driver->id}/license", ['license_expiry_date' => $newDate])
            ->assertRedirect(route('drivers'));

        $this->assertSame($newDate, $driver->fresh()->license_expiry_date->toDateString());
    }

    public function test_filters_narrow_the_table(): void
    {
        User::factory()->driver()->create(['agency_id' => $this->agency->id, 'name' => 'Findable Person', 'license_expiry_date' => now()->addYear()]);
        User::factory()->driver()->create(['agency_id' => $this->agency->id, 'name' => 'Other Person', 'license_expiry_date' => now()->subDay()]);

        $this->actingAs($this->admin);

        $this->get('/drivers?search=Findable')->assertSee('Findable Person')->assertDontSee('Other Person');
        $this->get('/drivers?license_status=Expired')->assertSee('Other Person')->assertDontSee('Findable Person');
    }

    public function test_cross_agency_driver_web_routes_return_404(): void
    {
        $foreign = User::factory()->driver()->create();

        $this->actingAs($this->admin);

        $this->put("/drivers/{$foreign->id}", ['name' => 'X', 'email' => 'x@example.com'])->assertNotFound();
        $this->patch("/drivers/{$foreign->id}/approve")->assertNotFound();
    }

    public function test_edit_vehicle_select_never_steals_another_drivers_vehicle(): void
    {
        $driver = User::factory()->driver()->create(['agency_id' => $this->agency->id]);
        $otherDriver = User::factory()->driver()->create(['agency_id' => $this->agency->id]);
        $stolen = Vehicle::factory()->create(['agency_id' => $this->agency->id, 'assigned_driver_id' => $otherDriver->id]);

        $this->actingAs($this->admin)
            ->put("/drivers/{$driver->id}", [
                'name' => $driver->name, 'email' => $driver->email, 'assigned_vehicle_id' => $stolen->id,
            ])->assertSessionHasErrors('assigned_vehicle_id');

        $this->assertSame($otherDriver->id, $stolen->fresh()->assigned_driver_id);
    }

    public function test_guest_is_redirected_to_login(): void
    {
        $this->get('/drivers')->assertRedirect(route('login'));
    }
}
