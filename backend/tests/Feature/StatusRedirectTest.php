<?php

namespace Tests\Feature;

use App\Models\Agency;
use App\Models\Dispatch;
use App\Models\User;
use App\Models\Vehicle;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * A status change must report back on the screen the admin was using.
 *
 * Every screen that shows a vehicle status can now write one (design decision 9),
 * and they all post to the same /vehicles/{id}/status route — which used to end
 * with a hardcoded redirect to the Vehicles page. Changing a status from
 * Inspections dropped the admin on Vehicles with the confirmation message there,
 * so on the screen they were actually looking at nothing appeared to happen; the
 * natural next move was the browser Back button, which replays a snapshot taken
 * before the change and shows the old status.
 */
class StatusRedirectTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private Vehicle $vehicle;

    protected function setUp(): void
    {
        parent::setUp();
        $agency = Agency::factory()->create(['code' => 'BFP']);
        $this->admin = User::factory()->admin()->create(['agency_id' => $agency->id]);
        $this->vehicle = Vehicle::factory()->create([
            'agency_id' => $agency->id,
            'status' => Vehicle::STATUS_OPERATIONAL,
        ]);
    }

    public static function originatingPages(): array
    {
        return [
            'vehicles' => ['/vehicles'],
            'inspections & damage' => ['/inspections'],
            'repairs' => ['/repairs'],
            'pm schedules' => ['/pm'],
        ];
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('originatingPages')]
    public function test_a_status_change_returns_to_the_page_it_was_made_from(string $from): void
    {
        $this->actingAs($this->admin)
            ->from($from)
            ->patch("/vehicles/{$this->vehicle->id}/status", ['status' => Vehicle::STATUS_NOT_OPERATIONAL])
            ->assertRedirect($from)
            ->assertSessionHas('status', 'Vehicle status updated.');

        $this->assertSame(Vehicle::STATUS_NOT_OPERATIONAL, $this->vehicle->fresh()->status);
    }

    /**
     * The active-dispatch refusal must land back on the originating page too —
     * and that page must actually render it. Four of the six dashboard pages had
     * no $errors block, so the guard's message was being discarded and the change
     * failed in silence.
     */
    #[\PHPUnit\Framework\Attributes\DataProvider('originatingPages')]
    public function test_a_refused_status_change_reports_on_the_originating_page(string $from): void
    {
        Dispatch::factory()->create([
            'agency_id' => $this->admin->agency_id,
            'vehicle_id' => $this->vehicle->id,
            'driver_id' => User::factory()->driver()->create(['agency_id' => $this->admin->agency_id])->id,
            'location' => 'Brgy. Obrero',
            'time_in' => null,
        ]);
        // Opening a dispatch is what puts the vehicle in its Dispatched state (FR-15).
        app(\App\Services\VehicleStatusWriter::class)->writeFromDispatch(
            $this->vehicle,
            Vehicle::STATUS_DISPATCHED,
            \App\Services\VehicleStatusWriter::SOURCE_DISPATCH_OPEN,
        );

        $this->actingAs($this->admin)
            ->from($from)
            ->patch("/vehicles/{$this->vehicle->id}/status", ['status' => Vehicle::STATUS_OPERATIONAL])
            ->assertRedirect($from)
            ->assertSessionHasErrors('status');

        // The vehicle keeps its Dispatched state (the writer refused the write).
        $this->assertSame(Vehicle::STATUS_DISPATCHED, $this->vehicle->fresh()->status);

        // And the admin can SEE why, on the page they were on.
        $this->actingAs($this->admin)
            ->get($from)
            ->assertOk()
            ->assertSee('is on an active dispatch', false)
            ->assertSee('Brgy. Obrero', false);
    }
}
