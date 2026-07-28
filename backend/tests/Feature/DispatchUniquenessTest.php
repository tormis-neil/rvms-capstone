<?php

namespace Tests\Feature;

use App\Models\Agency;
use App\Models\Dispatch;
use App\Models\User;
use App\Models\Vehicle;
use App\Services\VehicleStatusWriter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * A vehicle can only be out on one mission at a time, and a driver can only be
 * out on one at a time (FR-15, FR-18).
 *
 * The New Dispatch dropdown only lists Operational vehicles, so a dispatched
 * vehicle disappears from it — but that is a UI-only guard with two holes: the
 * API has no dropdown, and an agency with two administrators (BFP has two by
 * design) can have both pages loaded before either dispatches, so both still
 * show the vehicle as available.
 */
class DispatchUniquenessTest extends TestCase
{
    use RefreshDatabase;

    private Agency $agency;

    private User $admin;

    private User $secondAdmin;

    private User $driver;

    private Vehicle $vehicle;

    protected function setUp(): void
    {
        parent::setUp();
        $this->agency = Agency::factory()->create(['code' => 'BFP']);
        $this->admin = User::factory()->admin()->create(['agency_id' => $this->agency->id]);
        $this->secondAdmin = User::factory()->admin()->create(['agency_id' => $this->agency->id]);
        $this->driver = User::factory()->driver()->create(['agency_id' => $this->agency->id]);
        $this->vehicle = Vehicle::factory()->create([
            'agency_id' => $this->agency->id,
            'plate_number' => 'BFP-0001',
            'status' => Vehicle::STATUS_OPERATIONAL,
        ]);
    }

    private function payload(array $overrides = []): array
    {
        return array_merge([
            'vehicle_id' => $this->vehicle->id,
            'driver_id' => $this->driver->id,
            'mission_type' => 'Patrol',
            'location' => 'Brgy. Obrero',
            'time_out' => now()->toDateTimeString(),
        ], $overrides);
    }

    /** The realistic case: two admins of the same agency, both pages loaded. */
    public function test_a_second_admin_cannot_dispatch_a_vehicle_that_is_already_out(): void
    {
        $this->actingAs($this->admin)->from('/dispatch')
            ->post('/dispatch', $this->payload())
            ->assertRedirect('/dispatch')
            ->assertSessionHasNoErrors();

        $this->actingAs($this->secondAdmin)->from('/dispatch')
            ->post('/dispatch', $this->payload(['location' => 'Brgy. Aguit-itan']))
            ->assertRedirect('/dispatch')
            ->assertSessionHasErrors('vehicle_id');

        $this->assertSame(1, Dispatch::withoutGlobalScopes()
            ->where('vehicle_id', $this->vehicle->id)->whereNull('time_in')->count());
    }

    /** The refusal has to name the mission holding the vehicle. */
    public function test_the_refusal_names_the_open_mission(): void
    {
        $this->actingAs($this->admin)->from('/dispatch')->post('/dispatch', $this->payload());

        $errors = $this->actingAs($this->admin)->from('/dispatch')
            ->post('/dispatch', $this->payload())
            ->assertSessionHasErrors('vehicle_id')
            ->getSession()->get('errors');

        $message = $errors->first('vehicle_id');
        $this->assertStringContainsString('BFP-0001', $message);
        $this->assertStringContainsString('Patrol', $message);
        $this->assertStringContainsString('Brgy. Obrero', $message);
    }

    /** Closing the first dispatch releases the vehicle for the next one. */
    public function test_the_vehicle_can_be_dispatched_again_once_closed(): void
    {
        $this->actingAs($this->admin)->from('/dispatch')->post('/dispatch', $this->payload());
        $open = Dispatch::withoutGlobalScopes()->whereNull('time_in')->firstOrFail();

        $this->actingAs($this->admin)->from('/dispatch')
            ->patch("/dispatch/{$open->id}/close", [
                'time_in' => now()->toDateTimeString(),
                'return_status' => Vehicle::STATUS_OPERATIONAL,
            ])->assertSessionHasNoErrors();

        $this->actingAs($this->admin)->from('/dispatch')
            ->post('/dispatch', $this->payload())
            ->assertSessionHasNoErrors();

        $this->assertSame(2, Dispatch::withoutGlobalScopes()
            ->where('vehicle_id', $this->vehicle->id)->count());
    }

    /** Editing an open dispatch must not collide with itself. */
    public function test_editing_an_open_dispatch_is_still_allowed(): void
    {
        $this->actingAs($this->admin)->from('/dispatch')->post('/dispatch', $this->payload());
        $open = Dispatch::withoutGlobalScopes()->whereNull('time_in')->firstOrFail();

        $this->actingAs($this->admin)->from('/dispatch')
            ->put("/dispatch/{$open->id}", $this->payload(['location' => 'Brgy. Aguit-itan']))
            ->assertSessionHasNoErrors();

        $this->assertSame('Brgy. Aguit-itan', $open->fresh()->location);
    }

    /** The API is the hole the dropdown cannot cover. */
    public function test_the_api_refuses_a_second_open_dispatch(): void
    {
        $this->actingAs($this->admin, 'sanctum')
            ->postJson('/api/v1/dispatches', $this->payload())
            ->assertCreated();

        $this->actingAs($this->admin, 'sanctum')
            ->postJson('/api/v1/dispatches', $this->payload())
            ->assertStatus(422)
            ->assertJsonValidationErrors('vehicle_id');
    }

    /** One driver, two vehicles, at the same time — physically impossible. */
    public function test_a_driver_cannot_be_out_on_two_dispatches_at_once(): void
    {
        $second = Vehicle::factory()->create([
            'agency_id' => $this->agency->id,
            'plate_number' => 'BFP-0002',
            'status' => Vehicle::STATUS_OPERATIONAL,
        ]);

        $this->actingAs($this->admin)->from('/dispatch')
            ->post('/dispatch', $this->payload())
            ->assertSessionHasNoErrors();

        // Different vehicle, so vehicle_id is clean — only the driver clashes.
        $this->actingAs($this->admin)->from('/dispatch')
            ->post('/dispatch', $this->payload(['vehicle_id' => $second->id]))
            ->assertSessionHasErrors('driver_id')
            ->assertSessionDoesntHaveErrors('vehicle_id');

        $this->assertSame(1, Dispatch::withoutGlobalScopes()
            ->where('driver_id', $this->driver->id)->whereNull('time_in')->count());
        $this->assertSame(Vehicle::STATUS_OPERATIONAL, $second->fresh()->status);
    }

    public function test_the_driver_refusal_names_the_mission_and_vehicle(): void
    {
        $second = Vehicle::factory()->create([
            'agency_id' => $this->agency->id, 'plate_number' => 'BFP-0002',
            'status' => Vehicle::STATUS_OPERATIONAL,
        ]);
        $this->actingAs($this->admin)->from('/dispatch')->post('/dispatch', $this->payload());

        $message = $this->actingAs($this->admin)->from('/dispatch')
            ->post('/dispatch', $this->payload(['vehicle_id' => $second->id]))
            ->getSession()->get('errors')->first('driver_id');

        $this->assertStringContainsString($this->driver->name, $message);
        $this->assertStringContainsString('BFP-0001', $message);
        $this->assertStringContainsString('Patrol', $message);
    }

    /** Another driver may take a different vehicle at the same time. */
    public function test_a_different_driver_may_go_out_simultaneously(): void
    {
        $second = Vehicle::factory()->create([
            'agency_id' => $this->agency->id, 'plate_number' => 'BFP-0002',
            'status' => Vehicle::STATUS_OPERATIONAL,
        ]);
        $otherDriver = User::factory()->driver()->create(['agency_id' => $this->agency->id]);

        $this->actingAs($this->admin)->from('/dispatch')->post('/dispatch', $this->payload());

        $this->actingAs($this->admin)->from('/dispatch')
            ->post('/dispatch', $this->payload([
                'vehicle_id' => $second->id,
                'driver_id' => $otherDriver->id,
            ]))
            ->assertSessionHasNoErrors();

        $this->assertSame(2, Dispatch::withoutGlobalScopes()->whereNull('time_in')->count());
    }

    /**
     * The guard must also hold when validation is bypassed entirely — this is
     * the layer that survives the same-instant race between two admins.
     */
    public function test_the_locked_recheck_refuses_a_clash_on_its_own(): void
    {
        Dispatch::factory()->create([
            'agency_id' => $this->agency->id,
            'vehicle_id' => $this->vehicle->id,
            'driver_id' => $this->driver->id,
            'time_in' => null,
        ]);

        $guard = app(\App\Services\DispatchGuard::class);

        try {
            $guard->assertFree($this->vehicle->id, $this->driver->id);
            $this->fail('assertFree should have refused a vehicle that is already out.');
        } catch (\Illuminate\Validation\ValidationException $e) {
            $this->assertArrayHasKey('vehicle_id', $e->errors());
        }

        // A free vehicle and a free driver pass.
        $freeVehicle = Vehicle::factory()->create(['agency_id' => $this->agency->id]);
        $freeDriver = User::factory()->driver()->create(['agency_id' => $this->agency->id]);
        $guard->assertFree($freeVehicle->id, $freeDriver->id);
        $this->assertTrue(true);
    }

    /** Both layers must word the refusal identically. */
    public function test_both_layers_produce_the_same_message(): void
    {
        $this->actingAs($this->admin)->from('/dispatch')->post('/dispatch', $this->payload());

        $fromValidation = $this->actingAs($this->admin)->from('/dispatch')
            ->post('/dispatch', $this->payload())
            ->getSession()->get('errors')->first('vehicle_id');

        try {
            app(\App\Services\DispatchGuard::class)->assertFree($this->vehicle->id, $this->driver->id);
            $this->fail('assertFree should have refused.');
        } catch (\Illuminate\Validation\ValidationException $e) {
            $this->assertSame($fromValidation, $e->errors()['vehicle_id'][0]);
        }
    }

    /** A vehicle already Dispatched by the seeder/another path is caught too. */
    public function test_a_vehicle_marked_dispatched_with_an_open_record_is_refused(): void
    {
        Dispatch::factory()->create([
            'agency_id' => $this->agency->id,
            'vehicle_id' => $this->vehicle->id,
            'driver_id' => $this->driver->id,
            'time_in' => null,
        ]);
        app(VehicleStatusWriter::class)->writeFromDispatch(
            $this->vehicle,
            Vehicle::STATUS_DISPATCHED,
            VehicleStatusWriter::SOURCE_DISPATCH_OPEN,
        );

        $this->actingAs($this->admin)->from('/dispatch')
            ->post('/dispatch', $this->payload())
            ->assertSessionHasErrors('vehicle_id');
    }
}
