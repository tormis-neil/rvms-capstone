<?php

namespace Tests\Feature;

use App\Models\Agency;
use App\Models\User;
use App\Models\Vehicle;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * R2 — the /vehicles dashboard page (Blade twin of the vehicle API).
 */
class WebVehiclePageTest extends TestCase
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

    public function test_page_lists_only_own_agency_vehicles(): void
    {
        $mine = Vehicle::factory()->create(['agency_id' => $this->agency->id, 'plate_number' => 'MIN-0001']);
        $foreign = Vehicle::factory()->create(['plate_number' => 'FOR-9999']);

        $this->actingAs($this->admin)
            ->get('/vehicles')
            ->assertOk()
            ->assertSee('MIN-0001')
            ->assertDontSee('FOR-9999');
    }

    public function test_filters_narrow_the_table(): void
    {
        Vehicle::factory()->create(['agency_id' => $this->agency->id, 'plate_number' => 'AAA-1111', 'type' => 'Fire Truck']);
        Vehicle::factory()->create(['agency_id' => $this->agency->id, 'plate_number' => 'BBB-2222', 'type' => 'Ambulance', 'status' => Vehicle::STATUS_NOT_OPERATIONAL]);

        $this->actingAs($this->admin);

        $this->get('/vehicles?type=Fire+Truck')->assertSee('AAA-1111')->assertDontSee('BBB-2222');
        $this->get('/vehicles?status=Not+Operational')->assertSee('BBB-2222')->assertDontSee('AAA-1111');
        $this->get('/vehicles?search=BBB')->assertSee('BBB-2222')->assertDontSee('AAA-1111');
    }

    public function test_store_update_and_status_flows_write_to_the_database(): void
    {
        $driver = User::factory()->driver()->create(['agency_id' => $this->agency->id]);

        $this->actingAs($this->admin);

        // Add (requires baseline remarks on initial registration)
        $this->from('/vehicles')->post('/vehicles', [
            'type' => 'Fire Truck',
            'plate_number' => 'NEW-0001',
            'make' => 'Isuzu',
            'model' => 'FTR 850',
            'current_mileage' => 100,
            'assigned_driver_id' => $driver->id,
        ])->assertSessionHasErrors('remarks');

        $this->post('/vehicles', [
            'type' => 'Fire Truck',
            'plate_number' => 'NEW-0001',
            'make' => 'Isuzu',
            'model' => 'FTR 850',
            'current_mileage' => 100,
            'assigned_driver_id' => $driver->id,
            'remarks' => 'Baseline intake check passed; unit ready for duty.',
        ])->assertRedirect(route('vehicles'));

        $vehicle = Vehicle::withoutGlobalScopes()->where('plate_number', 'NEW-0001')->firstOrFail();
        $this->assertSame($this->agency->id, $vehicle->agency_id);
        $this->assertSame('Baseline intake check passed; unit ready for duty.', $vehicle->remarks);
        $this->assertSame('Initial Registration', $vehicle->status_source);

        // Edit (omitting remarks preserves existing baseline/status remarks)
        $this->put("/vehicles/{$vehicle->id}", [
            'type' => 'Fire Truck',
            'plate_number' => 'NEW-0002',
            'make' => 'Isuzu',
            'model' => 'FTR 850',
            'current_mileage' => 200,
        ])->assertRedirect(route('vehicles'));
        $this->assertSame('NEW-0002', $vehicle->fresh()->plate_number);
        $this->assertSame('Baseline intake check passed; unit ready for duty.', $vehicle->fresh()->remarks);

        // Status (manual choices only)
        $this->patch("/vehicles/{$vehicle->id}/status", ['status' => Vehicle::STATUS_UNDER_PM, 'remarks' => 'Reason for the change.'])
            ->assertRedirect(route('vehicles'));
        $this->assertSame(Vehicle::STATUS_UNDER_PM, $vehicle->fresh()->status);

        // "Dispatched" cannot be set by hand from the dashboard (FR-15/FR-18)
        $this->from('/vehicles')
            ->patch("/vehicles/{$vehicle->id}/status", ['status' => Vehicle::STATUS_DISPATCHED, 'remarks' => 'Reason for the change.'])
            ->assertSessionHasErrors('status');
    }

    public function test_status_update_accepts_optional_remarks(): void
    {
        $vehicle = Vehicle::factory()->create(['agency_id' => $this->agency->id]);

        $this->actingAs($this->admin)
            ->patch("/vehicles/{$vehicle->id}/status", [
                'status' => Vehicle::STATUS_NOT_OPERATIONAL,
                'remarks' => 'Transmission slipping.',
            ])->assertRedirect(route('vehicles'));

        $this->assertSame('Transmission slipping.', $vehicle->fresh()->remarks);
    }

    public function test_cross_agency_vehicle_web_routes_return_404(): void
    {
        $foreign = Vehicle::factory()->create();

        $this->actingAs($this->admin);

        $this->put("/vehicles/{$foreign->id}", [
            'type' => 'Fire Truck',
            'plate_number' => 'XXX-0001',
            'make' => 'Isuzu',
            'model' => 'FTR 850',
            'current_mileage' => 1,
        ])->assertNotFound();

        $this->patch("/vehicles/{$foreign->id}/status", ['status' => Vehicle::STATUS_OPERATIONAL, 'remarks' => 'Reason for the change.'])
            ->assertNotFound();
    }

    public function test_guest_is_redirected_to_login(): void
    {
        $this->get('/vehicles')->assertRedirect(route('login'));
    }

    public function test_admin_can_register_vehicle_with_other_custom_type(): void
    {
        $this->actingAs($this->admin)
            ->post('/vehicles', [
                'plate_number' => 'PNP-9901',
                'type' => 'Other',
                'custom_type' => 'Patrol Motorcycle',
                'make' => 'Yamaha',
                'model' => 'WR250',
                'current_mileage' => 500,
                'remarks' => 'New patrol motorcycle inspected upon delivery.',
            ])
            ->assertRedirect(route('vehicles'))
            ->assertSessionHas('status', 'Vehicle registered successfully.');

        $this->assertDatabaseHas('vehicles', [
            'agency_id' => $this->agency->id,
            'plate_number' => 'PNP-9901',
            'type' => 'Patrol Motorcycle',
        ]);
    }

    public function test_admin_can_update_vehicle_with_other_custom_type(): void
    {
        $vehicle = Vehicle::factory()->create([
            'agency_id' => $this->agency->id,
            'plate_number' => 'PNP-9902',
            'type' => 'Service Vehicle',
        ]);

        $this->actingAs($this->admin)
            ->put("/vehicles/{$vehicle->id}", [
                'plate_number' => 'PNP-9902',
                'type' => 'Other',
                'custom_type' => 'Mobile Command Unit',
                'make' => 'Isuzu',
                'model' => 'NPR',
                'current_mileage' => 1200,
            ])
            ->assertRedirect(route('vehicles'))
            ->assertSessionHas('status', 'Vehicle details updated.');

        $this->assertSame('Mobile Command Unit', $vehicle->fresh()->type);
    }
}
