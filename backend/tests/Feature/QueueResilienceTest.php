<?php

namespace Tests\Feature;

use App\Jobs\SendFcmMessage;
use App\Models\Agency;
use App\Models\Notification;
use App\Models\PmSchedule;
use App\Models\User;
use App\Models\Vehicle;
use App\Services\Fcm\FakeFcmTransport;
use App\Services\Fcm\FcmMessage;
use App\Services\Fcm\FcmTransport;
use App\Services\MaintenanceAlerts;
use App\Services\VehicleStatusWriter;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * R10 sub-task 7 — retry, backoff and idempotency (NFR-04).
 *
 * NFR-04 promises "dependable delivery of notifications for time-sensitive
 * events". Dependable cuts both ways: a transient failure must be retried,
 * and a repeat must not produce a second alert. The second half is the one
 * that damages trust — an admin who is told twice about one thing stops
 * reading the alerts.
 */
class QueueResilienceTest extends TestCase
{
    use RefreshDatabase;

    private Agency $agency;

    private User $driver;

    private Vehicle $vehicle;

    protected function setUp(): void
    {
        parent::setUp();
        $this->agency = Agency::factory()->create(['code' => 'BFP']);
        User::factory()->admin()->create(['agency_id' => $this->agency->id]);
        $this->driver = User::factory()->driver()->create([
            'agency_id' => $this->agency->id,
            'fcm_token' => 'device-abc',
        ]);
        $this->vehicle = Vehicle::factory()->create([
            'agency_id' => $this->agency->id,
            'assigned_driver_id' => $this->driver->id,
            'status' => Vehicle::STATUS_OPERATIONAL,
        ]);
    }

    /* ------------------------------ the job ------------------------------- */

    public function test_the_push_job_retries_with_a_growing_backoff(): void
    {
        $job = new SendFcmMessage(new FcmMessage('device-abc', 'T', 'B'));

        $this->assertSame(3, $job->tries);
        $this->assertSame([10, 60], $job->backoff);
    }

    /**
     * An alert says a status changed NOW. A push that finally lands an hour
     * later — after a worker restart replays a stale queue — is worse than
     * one that never lands, because the row is already in the driver's inbox
     * and the status may have changed again since.
     */
    public function test_the_push_job_stops_retrying_after_ten_minutes(): void
    {
        $job = new SendFcmMessage(new FcmMessage('device-abc', 'T', 'B'));

        $this->assertEqualsWithDelta(
            now()->addMinutes(10)->timestamp,
            $job->retryUntil()->getTimestamp(),
            2,
        );
    }

    /* --------------------------- the scheduler ---------------------------- */

    /**
     * Every scheduled command must refuse to overlap itself: all three walk
     * every record in the agency, and two concurrent sweeps would race the
     * alert idempotency they depend on.
     */
    public function test_every_scheduled_command_refuses_to_overlap(): void
    {
        $events = collect(app(Schedule::class)->events());

        foreach (['rvms:recalculate-pm', 'rvms:pm-alerts', 'rvms:license-alerts'] as $command) {
            $event = $events->first(fn ($e) => str_contains($e->command ?? '', $command));

            $this->assertNotNull($event, "{$command} is not scheduled at all.");
            $this->assertTrue(
                $event->withoutOverlapping,
                "{$command} may overlap itself — two concurrent sweeps would race.",
            );
            $this->assertTrue(
                $event->onOneServer,
                "{$command} would run on every server if a second is ever added.",
            );
        }
    }

    /* ---------------------------- idempotency ----------------------------- */

    /** Re-running the daily sweep must not re-alert on the same schedule. */
    public function test_the_pm_sweep_does_not_alert_twice_for_one_schedule(): void
    {
        PmSchedule::factory()->create([
            'agency_id' => $this->agency->id,
            'vehicle_id' => $this->vehicle->id,
            'status' => PmSchedule::STATUS_DUE,
        ]);

        $this->artisan('rvms:pm-alerts')->assertExitCode(0);
        $after = Notification::withoutGlobalScopes()->count();

        // The sweep runs daily; the schedule stays Due until someone services
        // it, so every subsequent run must stay silent.
        $this->artisan('rvms:pm-alerts')->assertExitCode(0);
        $this->artisan('rvms:pm-alerts')->assertExitCode(0);

        $this->assertSame($after, Notification::withoutGlobalScopes()->count());
    }

    /**
     * The immediate path and the daily sweep both call MaintenanceAlerts, so
     * a schedule an admin creates already-due must alert exactly once — not
     * once now and again at 01:15.
     */
    public function test_the_immediate_alert_and_the_sweep_never_double_up(): void
    {
        $schedule = PmSchedule::factory()->create([
            'agency_id' => $this->agency->id,
            'vehicle_id' => $this->vehicle->id,
            'status' => PmSchedule::STATUS_DUE,
        ]);

        app(MaintenanceAlerts::class)->raiseForSchedule($schedule);
        $afterImmediate = Notification::withoutGlobalScopes()->count();

        $this->artisan('rvms:pm-alerts');

        $this->assertSame($afterImmediate, Notification::withoutGlobalScopes()->count());
    }

    /** A licence sitting expired must not alert every single morning. */
    public function test_the_licence_sweep_does_not_alert_twice_for_one_driver(): void
    {
        $this->driver->update(['license_expiry_date' => now()->subWeek()]);

        $this->artisan('rvms:license-alerts')->assertExitCode(0);
        $after = Notification::withoutGlobalScopes()->count();

        $this->artisan('rvms:license-alerts')->assertExitCode(0);

        $this->assertSame($after, Notification::withoutGlobalScopes()->count());
    }

    /* ------------------------ idempotent status writes -------------------- */

    /** Writing the same status again is a no-op that notifies nobody. */
    public function test_rewriting_the_same_status_raises_no_notification(): void
    {
        $writer = app(VehicleStatusWriter::class);
        $before = Notification::withoutGlobalScopes()->count();

        $writer->write($this->vehicle, Vehicle::STATUS_OPERATIONAL, VehicleStatusWriter::SOURCE_VEHICLES);
        $writer->write($this->vehicle->fresh(), Vehicle::STATUS_OPERATIONAL, VehicleStatusWriter::SOURCE_REPAIR);

        $this->assertSame($before, Notification::withoutGlobalScopes()->count());
    }

    /** A real change still notifies — the guard must not silence everything. */
    public function test_a_real_status_change_still_notifies_the_driver(): void
    {
        $before = Notification::withoutGlobalScopes()->count();

        app(VehicleStatusWriter::class)->write(
            $this->vehicle,
            Vehicle::STATUS_NOT_OPERATIONAL,
            VehicleStatusWriter::SOURCE_VEHICLES,
        );

        $this->assertSame($before + 1, Notification::withoutGlobalScopes()->count());
    }

    /* -------------------------- delivery failure -------------------------- */

    /**
     * A dead handset must not cost the stored row. The push is best-effort on
     * top of a record that is already written — that separation is what makes
     * FR-21 hold when Firebase does not.
     */
    public function test_a_failed_push_never_removes_the_stored_notification(): void
    {
        $fake = new FakeFcmTransport;
        $fake->rejects = ['device-abc'];
        $this->app->instance(FcmTransport::class, $fake);

        app(VehicleStatusWriter::class)->write(
            $this->vehicle,
            Vehicle::STATUS_NOT_OPERATIONAL,
            VehicleStatusWriter::SOURCE_VEHICLES,
        );

        $this->assertDatabaseHas('notifications', [
            'user_id' => $this->driver->id,
            'type' => Notification::TYPE_VEHICLE_STATUS_UPDATE,
        ]);
    }
}
