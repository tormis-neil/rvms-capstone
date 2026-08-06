<?php

namespace Tests\Feature;

use App\Models\Agency;
use App\Models\Dispatch;
use App\Models\User;
use App\Models\Vehicle;
use App\Services\VehicleStatusWriter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/**
 * Editing an OPEN dispatch onto a different vehicle moves the Dispatched
 * status with it (FR-15, FR-16, FR-18 — 2026-08, lead-reported).
 *
 * Opening and closing always wrote the vehicle's status; editing never did,
 * and the edit form carries `vehicle_id`. Correcting "I dispatched the wrong
 * truck" therefore left the NEW vehicle out on a mission while still reading
 * Operational — contradicting the Vehicles page, the dashboard, the
 * availability list and the vehicle-status report at once — and stranded the
 * OLD one as Dispatched with nothing left to release it.
 *
 * The suite is written against BOTH front doors, because the bug was in both
 * and a fix to one would be a silent half-fix.
 */
class DispatchReassignmentTest extends TestCase
{
    use RefreshDatabase;

    private Agency $agency;

    private User $admin;

    private User $driver;

    private Vehicle $original;

    private Vehicle $replacement;

    protected function setUp(): void
    {
        parent::setUp();
        $this->agency = Agency::factory()->create(['code' => 'BFP']);
        $this->admin = User::factory()->admin()->create(['agency_id' => $this->agency->id]);
        $this->driver = User::factory()->driver()->create(['agency_id' => $this->agency->id]);

        $this->original = Vehicle::factory()->create([
            'agency_id' => $this->agency->id,
            'plate_number' => 'BFP-0001',
            'status' => Vehicle::STATUS_OPERATIONAL,
        ]);
        $this->replacement = Vehicle::factory()->create([
            'agency_id' => $this->agency->id,
            'plate_number' => 'BFP-0002',
            'status' => Vehicle::STATUS_OPERATIONAL,
        ]);
    }

    /** An open dispatch on $original, with the vehicle marked as a real one would be. */
    private function openDispatch(): Dispatch
    {
        $dispatch = Dispatch::create([
            'agency_id' => $this->agency->id,
            'vehicle_id' => $this->original->id,
            'driver_id' => $this->driver->id,
            'mission_type' => 'Patrol',
            'location' => 'Brgy. Obrero',
            'time_out' => now()->subHour(),
        ]);

        app(VehicleStatusWriter::class)->writeFromDispatch(
            $this->original,
            Vehicle::STATUS_DISPATCHED,
            VehicleStatusWriter::SOURCE_DISPATCH_OPEN,
        );

        return $dispatch;
    }

    private function payload(array $overrides = []): array
    {
        return array_merge([
            'vehicle_id' => $this->original->id,
            'driver_id' => $this->driver->id,
            'mission_type' => 'Patrol',
            'location' => 'Brgy. Obrero',
            'time_out' => now()->subHour()->toDateTimeString(),
        ], $overrides);
    }

    /* ------------------------------- the API ------------------------------ */

    public function test_moving_an_open_dispatch_marks_the_new_vehicle_dispatched(): void
    {
        $dispatch = $this->openDispatch();
        Sanctum::actingAs($this->admin);

        $this->putJson("/api/v1/dispatches/{$dispatch->id}", $this->payload([
            'vehicle_id' => $this->replacement->id,
        ]))->assertOk();

        $this->assertSame(Vehicle::STATUS_DISPATCHED, $this->replacement->fresh()->status);
    }

    public function test_moving_an_open_dispatch_releases_the_old_vehicle(): void
    {
        $dispatch = $this->openDispatch();
        Sanctum::actingAs($this->admin);

        $this->putJson("/api/v1/dispatches/{$dispatch->id}", $this->payload([
            'vehicle_id' => $this->replacement->id,
        ]))->assertOk();

        // Operational, not stranded on Dispatched with nothing to release it.
        $this->assertSame(Vehicle::STATUS_OPERATIONAL, $this->original->fresh()->status);
    }

    /** The released vehicle says where it came from, and it is not "Closed". */
    public function test_the_released_vehicle_records_the_reassignment_as_its_source(): void
    {
        $dispatch = $this->openDispatch();
        Sanctum::actingAs($this->admin);

        $this->putJson("/api/v1/dispatches/{$dispatch->id}", $this->payload([
            'vehicle_id' => $this->replacement->id,
        ]))->assertOk();

        $this->assertSame(
            VehicleStatusWriter::SOURCE_DISPATCH_EDIT,
            $this->original->fresh()->status_source,
        );
    }

    /** Editing anything else must not touch a status. */
    public function test_editing_without_changing_the_vehicle_leaves_the_status_alone(): void
    {
        $dispatch = $this->openDispatch();
        Sanctum::actingAs($this->admin);

        $this->putJson("/api/v1/dispatches/{$dispatch->id}", $this->payload([
            'location' => 'Brgy. Aguit-itan',
        ]))->assertOk();

        $this->assertSame(Vehicle::STATUS_DISPATCHED, $this->original->fresh()->status);
        $this->assertSame(
            VehicleStatusWriter::SOURCE_DISPATCH_OPEN,
            $this->original->fresh()->status_source,
        );
    }

    /**
     * A closed dispatch is history. Correcting which vehicle it names must not
     * put a parked vehicle back out on a mission.
     */
    public function test_editing_a_closed_dispatch_does_not_dispatch_anything(): void
    {
        $dispatch = Dispatch::create([
            'agency_id' => $this->agency->id,
            'vehicle_id' => $this->original->id,
            'driver_id' => $this->driver->id,
            'mission_type' => 'Patrol',
            'location' => 'Brgy. Obrero',
            'time_out' => now()->subDays(2),
            'time_in' => now()->subDays(2)->addHours(3),
            'return_status' => Vehicle::STATUS_OPERATIONAL,
        ]);

        Sanctum::actingAs($this->admin);

        $this->putJson("/api/v1/dispatches/{$dispatch->id}", $this->payload([
            'vehicle_id' => $this->replacement->id,
            'time_out' => now()->subDays(2)->toDateTimeString(),
        ]))->assertOk();

        $this->assertSame(Vehicle::STATUS_OPERATIONAL, $this->replacement->fresh()->status);
        $this->assertSame(Vehicle::STATUS_OPERATIONAL, $this->original->fresh()->status);
    }

    /** The uniqueness rule still applies to an edit — it just ignores itself. */
    public function test_an_edit_cannot_steal_a_vehicle_that_is_already_out(): void
    {
        $dispatch = $this->openDispatch();

        // A second mission holding the replacement vehicle.
        $secondDriver = User::factory()->driver()->create(['agency_id' => $this->agency->id]);
        Dispatch::create([
            'agency_id' => $this->agency->id,
            'vehicle_id' => $this->replacement->id,
            'driver_id' => $secondDriver->id,
            'mission_type' => 'Fire Response',
            'location' => 'Brgy. Payahan',
            'time_out' => now()->subMinutes(30),
        ]);
        app(VehicleStatusWriter::class)->writeFromDispatch(
            $this->replacement,
            Vehicle::STATUS_DISPATCHED,
            VehicleStatusWriter::SOURCE_DISPATCH_OPEN,
        );

        Sanctum::actingAs($this->admin);

        $this->putJson("/api/v1/dispatches/{$dispatch->id}", $this->payload([
            'vehicle_id' => $this->replacement->id,
        ]))->assertStatus(422)->assertJsonValidationErrors('vehicle_id');

        // Neither vehicle moved.
        $this->assertSame(Vehicle::STATUS_DISPATCHED, $this->original->fresh()->status);
        $this->assertSame($this->original->id, $dispatch->fresh()->vehicle_id);
    }

    /* ----------------------------- the dashboard --------------------------- */

    public function test_the_web_edit_hands_the_status_over_too(): void
    {
        $dispatch = $this->openDispatch();

        $this->actingAs($this->admin)
            ->from('/dispatch')
            ->put(route('dispatch.update', $dispatch), $this->payload([
                'vehicle_id' => $this->replacement->id,
            ]))
            ->assertRedirect('/dispatch');

        $this->assertSame(Vehicle::STATUS_DISPATCHED, $this->replacement->fresh()->status);
        $this->assertSame(Vehicle::STATUS_OPERATIONAL, $this->original->fresh()->status);
    }

    /**
     * The whole point of the fix: after an edit, no vehicle is out on a mission
     * while claiming to be available. This is FR-18 stated as an invariant.
     */
    public function test_no_vehicle_is_ever_out_while_reading_operational(): void
    {
        $dispatch = $this->openDispatch();

        $this->actingAs($this->admin)
            ->put(route('dispatch.update', $dispatch), $this->payload([
                'vehicle_id' => $this->replacement->id,
            ]));

        $outOnMission = Dispatch::query()->whereNull('time_in')->pluck('vehicle_id');

        foreach ($outOnMission as $vehicleId) {
            $this->assertSame(
                Vehicle::STATUS_DISPATCHED,
                Vehicle::query()->find($vehicleId)->status,
                'A vehicle with an open dispatch is not marked Dispatched.',
            );
        }

        // And nothing is left Dispatched without a mission to explain it.
        $stranded = Vehicle::query()
            ->where('status', Vehicle::STATUS_DISPATCHED)
            ->whereNotIn('id', $outOnMission)
            ->count();

        $this->assertSame(0, $stranded, 'A vehicle is stranded on Dispatched with no open dispatch.');
    }
}
