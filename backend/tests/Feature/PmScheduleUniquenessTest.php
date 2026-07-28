<?php

namespace Tests\Feature;

use App\Models\Agency;
use App\Models\PmSchedule;
use App\Models\User;
use App\Models\Vehicle;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * One ACTIVE preventive-maintenance schedule per vehicle per service target
 * (FR-14). A vehicle legitimately has several schedules for different targets;
 * two active ones for the same target are a duplicate, and the recalculation
 * job would flip both — two Due Soon rows and two reminders for one job.
 */
class PmScheduleUniquenessTest extends TestCase
{
    use RefreshDatabase;

    private Agency $agency;

    private User $admin;

    private Vehicle $vehicle;

    protected function setUp(): void
    {
        parent::setUp();
        $this->agency = Agency::factory()->create(['code' => 'BFP']);
        $this->admin = User::factory()->admin()->create(['agency_id' => $this->agency->id]);
        $this->vehicle = Vehicle::factory()->create(['agency_id' => $this->agency->id]);
    }

    private function payload(array $overrides = []): array
    {
        return array_merge([
            'vehicle_id' => $this->vehicle->id,
            'service_target' => 'Oil Change & Filter',
            'pm_type' => PmSchedule::TYPE_MILEAGE,
            'interval_km' => 5000,
            'last_pm_mileage' => 1000,
            'due_soon_threshold_km' => 500,
        ], $overrides);
    }

    public function test_a_duplicate_active_schedule_is_refused(): void
    {
        $this->actingAs($this->admin)->from('/pm')
            ->post('/pm', $this->payload())
            ->assertSessionHasNoErrors();

        $this->actingAs($this->admin)->from('/pm')
            ->post('/pm', $this->payload())
            ->assertSessionHasErrors('service_target');

        $this->assertSame(1, PmSchedule::withoutGlobalScopes()
            ->where('vehicle_id', $this->vehicle->id)->count());
    }

    /** Different targets on the same vehicle are the normal case. */
    public function test_different_service_targets_on_one_vehicle_are_allowed(): void
    {
        foreach (['Oil Change & Filter', 'Brake Service', 'Tyre Rotation'] as $target) {
            $this->actingAs($this->admin)->from('/pm')
                ->post('/pm', $this->payload(['service_target' => $target]))
                ->assertSessionHasNoErrors();
        }

        $this->assertSame(3, PmSchedule::withoutGlobalScopes()
            ->where('vehicle_id', $this->vehicle->id)->count());
    }

    /** The same target on a DIFFERENT vehicle is unrelated. */
    public function test_the_same_target_on_another_vehicle_is_allowed(): void
    {
        $other = Vehicle::factory()->create(['agency_id' => $this->agency->id]);

        $this->actingAs($this->admin)->from('/pm')->post('/pm', $this->payload());
        $this->actingAs($this->admin)->from('/pm')
            ->post('/pm', $this->payload(['vehicle_id' => $other->id]))
            ->assertSessionHasNoErrors();

        $this->assertSame(2, PmSchedule::withoutGlobalScopes()->count());
    }

    /** Completing a cycle frees the target for the next one. */
    public function test_the_target_can_be_scheduled_again_once_completed(): void
    {
        $this->actingAs($this->admin)->from('/pm')->post('/pm', $this->payload());
        $schedule = PmSchedule::withoutGlobalScopes()->firstOrFail();

        $this->actingAs($this->admin)->from('/pm')
            ->patch("/pm/{$schedule->id}/complete", [
                'date_serviced' => now()->toDateString(),
                'completion_repair_source' => 'GSO Motorpool',
                'completion_parts_replaced' => 'Oil filter',
                'completion_remarks' => 'Done.',
            ])->assertSessionHasNoErrors();

        $this->assertSame(PmSchedule::STATUS_COMPLETED, $schedule->fresh()->status);

        $this->actingAs($this->admin)->from('/pm')
            ->post('/pm', $this->payload())
            ->assertSessionHasNoErrors();

        $this->assertSame(2, PmSchedule::withoutGlobalScopes()
            ->where('vehicle_id', $this->vehicle->id)->count());
    }

    /** Editing a schedule must not collide with itself. */
    public function test_editing_a_schedule_keeps_its_own_target(): void
    {
        $this->actingAs($this->admin)->from('/pm')->post('/pm', $this->payload());
        $schedule = PmSchedule::withoutGlobalScopes()->firstOrFail();

        $this->actingAs($this->admin)->from('/pm')
            ->put("/pm/{$schedule->id}", $this->payload(['interval_km' => 7500]))
            ->assertSessionHasNoErrors();

        $this->assertSame(7500, $schedule->fresh()->interval_km);
    }

    /** Another agency's identical schedule is none of this agency's business. */
    public function test_another_agency_is_unaffected(): void
    {
        $other = Agency::factory()->create(['code' => 'PNP']);
        $otherAdmin = User::factory()->admin()->create(['agency_id' => $other->id]);
        $otherVehicle = Vehicle::factory()->create(['agency_id' => $other->id]);

        $this->actingAs($this->admin)->from('/pm')->post('/pm', $this->payload());

        $this->actingAs($otherAdmin)->from('/pm')
            ->post('/pm', $this->payload(['vehicle_id' => $otherVehicle->id]))
            ->assertSessionHasNoErrors();

        $this->assertSame(2, PmSchedule::withoutGlobalScopes()->count());
    }

    public function test_the_api_refuses_a_duplicate_active_schedule(): void
    {
        $this->actingAs($this->admin, 'sanctum')
            ->postJson('/api/v1/pm-schedules', $this->payload())
            ->assertCreated();

        $this->actingAs($this->admin, 'sanctum')
            ->postJson('/api/v1/pm-schedules', $this->payload())
            ->assertStatus(422)
            ->assertJsonValidationErrors('service_target');
    }
}
