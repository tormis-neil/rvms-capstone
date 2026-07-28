<?php

namespace Tests\Feature;

use App\Models\Agency;
use App\Models\Notification;
use App\Models\PmSchedule;
use App\Models\User;
use App\Models\Vehicle;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

/**
 * The two scheduled alert commands (FR-08 → FR-21, FR-14 → FR-21).
 *
 * Both run daily, so the property that matters most is that they do NOT
 * re-alert: a licence expiring in three weeks would otherwise raise twenty-one
 * identical rows per administrator.
 */
class ScheduledAlertTest extends TestCase
{
    use RefreshDatabase;

    private Agency $agency;

    private User $admin;

    private User $secondAdmin;

    private User $driver;

    protected function setUp(): void
    {
        parent::setUp();
        Queue::fake();

        $this->agency = Agency::factory()->create([
            'code' => 'BFP',
            'license_expiry_warning_days' => 30,
        ]);
        $this->admin = User::factory()->admin()->create(['agency_id' => $this->agency->id]);
        $this->secondAdmin = User::factory()->admin()->create(['agency_id' => $this->agency->id]);
        $this->driver = User::factory()->driver()->create(['agency_id' => $this->agency->id]);
    }

    /* --------------------------- licence alerts -------------------------- */

    public function test_an_expiring_licence_alerts_every_admin(): void
    {
        $this->driver->update(['license_expiry_date' => now()->addDays(10)->toDateString()]);

        $this->artisan('rvms:license-alerts')->assertSuccessful();

        $this->assertDatabaseCount('notifications', 2);
        foreach ([$this->admin, $this->secondAdmin] as $recipient) {
            $this->assertDatabaseHas('notifications', [
                'user_id' => $recipient->id,
                'type' => Notification::TYPE_LICENSE_EXPIRING,
                'title' => 'License Expiring Soon',
            ]);
        }
    }

    public function test_an_expired_licence_uses_its_own_type(): void
    {
        $this->driver->update(['license_expiry_date' => now()->subDays(3)->toDateString()]);

        $this->artisan('rvms:license-alerts')->assertSuccessful();

        $this->assertDatabaseHas('notifications', [
            'user_id' => $this->admin->id,
            'type' => Notification::TYPE_LICENSE_EXPIRED,
            'title' => 'License Expired',
        ]);
    }

    /** The whole point of a daily job: running it twice must not double up. */
    public function test_running_twice_does_not_re_alert(): void
    {
        $this->driver->update(['license_expiry_date' => now()->addDays(10)->toDateString()]);

        $this->artisan('rvms:license-alerts')->assertSuccessful();
        $this->artisan('rvms:license-alerts')->assertSuccessful();

        $this->assertDatabaseCount('notifications', 2); // not 4
    }

    /** Crossing from Expiring Soon into Expired is genuinely new news. */
    public function test_crossing_into_expired_raises_a_fresh_alert(): void
    {
        $this->driver->update(['license_expiry_date' => now()->addDay()->toDateString()]);
        $this->artisan('rvms:license-alerts')->assertSuccessful();
        $this->assertDatabaseCount('notifications', 2);

        $this->travel(3)->days();
        $this->artisan('rvms:license-alerts')->assertSuccessful();

        $this->assertDatabaseCount('notifications', 4);
        $this->assertDatabaseHas('notifications', ['type' => Notification::TYPE_LICENSE_EXPIRED]);
    }

    /** A renewal must start a fresh cycle, not be silenced by the old alert. */
    public function test_a_renewed_licence_can_alert_again_later(): void
    {
        $this->driver->update(['license_expiry_date' => now()->addDays(10)->toDateString()]);
        $this->artisan('rvms:license-alerts')->assertSuccessful();
        $this->assertDatabaseCount('notifications', 2);

        // Renewed far out — nothing to say.
        $this->driver->update(['license_expiry_date' => now()->addYear()->toDateString()]);
        $this->artisan('rvms:license-alerts')->assertSuccessful();
        $this->assertDatabaseCount('notifications', 2);

        // A year on, the new licence approaches expiry: that IS new news.
        $this->driver->update(['license_expiry_date' => now()->addDays(5)->toDateString()]);
        $this->artisan('rvms:license-alerts')->assertSuccessful();
        $this->assertDatabaseCount('notifications', 4);
    }

    public function test_a_valid_licence_alerts_nobody(): void
    {
        $this->driver->update(['license_expiry_date' => now()->addMonths(6)->toDateString()]);

        $this->artisan('rvms:license-alerts')->assertSuccessful();

        $this->assertDatabaseCount('notifications', 0);
    }

    /** The window is the agency's own column, never a constant. */
    public function test_the_warning_window_is_the_agencys_own_threshold(): void
    {
        $this->driver->update(['license_expiry_date' => now()->addDays(45)->toDateString()]);

        $this->artisan('rvms:license-alerts')->assertSuccessful();
        $this->assertDatabaseCount('notifications', 0); // outside a 30-day window

        $this->agency->update(['license_expiry_warning_days' => 60]);
        $this->artisan('rvms:license-alerts')->assertSuccessful();
        $this->assertDatabaseCount('notifications', 2); // inside a 60-day window
    }

    public function test_alerts_never_cross_agencies(): void
    {
        $other = Agency::factory()->create(['code' => 'PNP']);
        $otherAdmin = User::factory()->admin()->create(['agency_id' => $other->id]);
        $this->driver->update(['license_expiry_date' => now()->addDays(10)->toDateString()]);

        $this->artisan('rvms:license-alerts')->assertSuccessful();

        $this->assertDatabaseMissing('notifications', ['user_id' => $otherAdmin->id]);
    }

    /* ------------------------------ PM alerts ---------------------------- */

    private int $plateSeq = 0;

    private function schedule(string $status): PmSchedule
    {
        // A distinct plate per call: plates are unique per agency (design
        // decision 10), and one of these tests creates two vehicles.
        $vehicle = Vehicle::factory()->create([
            'agency_id' => $this->agency->id,
            'plate_number' => sprintf('BFP-%04d', ++$this->plateSeq),
            'assigned_driver_id' => $this->driver->id,
        ]);

        return PmSchedule::factory()->create([
            'agency_id' => $this->agency->id,
            'vehicle_id' => $vehicle->id,
            'service_target' => 'Oil Change & Filter',
            'status' => $status,
        ]);
    }

    public function test_a_due_schedule_alerts_admins_and_reminds_the_driver(): void
    {
        $this->schedule(PmSchedule::STATUS_DUE);

        $this->artisan('rvms:pm-alerts')->assertSuccessful();

        // Two admins + the assigned driver.
        $this->assertDatabaseCount('notifications', 3);
        $this->assertDatabaseHas('notifications', [
            'user_id' => $this->admin->id,
            'type' => Notification::TYPE_PM_DUE,
            'title' => 'PM Due',
        ]);
        $this->assertDatabaseHas('notifications', [
            'user_id' => $this->driver->id,
            'type' => Notification::TYPE_PM_REMINDER,
            'title' => 'Preventive Maintenance',
        ]);
    }

    public function test_a_due_soon_schedule_uses_its_own_type(): void
    {
        $this->schedule(PmSchedule::STATUS_DUE_SOON);

        $this->artisan('rvms:pm-alerts')->assertSuccessful();

        $this->assertDatabaseHas('notifications', [
            'user_id' => $this->admin->id,
            'type' => Notification::TYPE_PM_DUE_SOON,
            'title' => 'PM Due Soon',
        ]);
    }

    public function test_pm_alerts_do_not_repeat_daily(): void
    {
        $this->schedule(PmSchedule::STATUS_DUE_SOON);

        $this->artisan('rvms:pm-alerts')->assertSuccessful();
        $this->artisan('rvms:pm-alerts')->assertSuccessful();

        $this->assertDatabaseCount('notifications', 3); // not 6
    }

    /** Due Soon → Due is a real escalation and alerts again. */
    public function test_escalating_to_due_raises_a_fresh_alert(): void
    {
        $schedule = $this->schedule(PmSchedule::STATUS_DUE_SOON);
        $this->artisan('rvms:pm-alerts')->assertSuccessful();
        $this->assertDatabaseCount('notifications', 3);

        $schedule->update(['status' => PmSchedule::STATUS_DUE]);
        $this->artisan('rvms:pm-alerts')->assertSuccessful();

        $this->assertDatabaseCount('notifications', 6);
        $this->assertDatabaseHas('notifications', ['type' => Notification::TYPE_PM_DUE]);
    }

    public function test_upcoming_and_completed_schedules_alert_nobody(): void
    {
        $this->schedule(PmSchedule::STATUS_UPCOMING);
        $this->schedule(PmSchedule::STATUS_COMPLETED);

        $this->artisan('rvms:pm-alerts')->assertSuccessful();

        $this->assertDatabaseCount('notifications', 0);
    }

    public function test_a_vehicle_without_a_driver_still_alerts_the_admins(): void
    {
        $vehicle = Vehicle::factory()->create([
            'agency_id' => $this->agency->id,
            'assigned_driver_id' => null,
        ]);
        PmSchedule::factory()->create([
            'agency_id' => $this->agency->id,
            'vehicle_id' => $vehicle->id,
            'status' => PmSchedule::STATUS_DUE,
        ]);

        $this->artisan('rvms:pm-alerts')->assertSuccessful();

        $this->assertDatabaseCount('notifications', 2); // admins only
    }

    public function test_both_commands_are_registered_in_the_scheduler(): void
    {
        $events = collect(app(\Illuminate\Console\Scheduling\Schedule::class)->events())
            ->map(fn ($event) => $event->command)
            ->implode(' ');

        $this->assertStringContainsString('rvms:recalculate-pm', $events);
        $this->assertStringContainsString('rvms:pm-alerts', $events);
        $this->assertStringContainsString('rvms:license-alerts', $events);
    }
}
