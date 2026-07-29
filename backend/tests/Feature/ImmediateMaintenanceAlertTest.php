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
 * PM and licence alerts are TIME-driven, so a daily command sweeps for them.
 * But that leaves a hole: an admin who creates a schedule already past due, or
 * records a licence already inside the warning window, would wait up to a day
 * to be told about something they just entered — and in a demo nobody runs an
 * artisan command at all (lead-reported).
 *
 * These prove the alert now fires from the admin's own action, WITHOUT any
 * command being run, and that the two paths never double-alert.
 */
class ImmediateMaintenanceAlertTest extends TestCase
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
        Queue::fake();

        $this->agency = Agency::factory()->create([
            'code' => 'BFP',
            'license_expiry_warning_days' => 30,
        ]);
        $this->admin = User::factory()->admin()->create(['agency_id' => $this->agency->id]);
        $this->secondAdmin = User::factory()->admin()->create(['agency_id' => $this->agency->id]);
        $this->driver = User::factory()->driver()->create(['agency_id' => $this->agency->id]);
        $this->vehicle = Vehicle::factory()->create([
            'agency_id' => $this->agency->id,
            'plate_number' => 'BFP-0001',
            'assigned_driver_id' => $this->driver->id,
            'current_mileage' => 50_000,
        ]);
    }

    /* ------------------------- PM created already due -------------------- */

    /** last_pm 40 000 + interval 5 000 = due at 45 000, and the vehicle is at 50 000. */
    private function overduePayload(array $overrides = []): array
    {
        return array_merge([
            'vehicle_id' => $this->vehicle->id,
            'service_target' => 'Oil Change & Filter',
            'pm_type' => PmSchedule::TYPE_MILEAGE,
            'interval_km' => 5_000,
            'last_pm_mileage' => 40_000,
            'due_soon_threshold_km' => 500,
        ], $overrides);
    }

    public function test_creating_an_already_due_schedule_alerts_immediately(): void
    {
        $this->actingAs($this->admin)->from('/pm')
            ->post('/pm', $this->overduePayload())
            ->assertSessionHasNoErrors();

        // No command was run. Both admins + the assigned driver.
        $this->assertDatabaseCount('notifications', 3);
        $this->assertDatabaseHas('notifications', [
            'user_id' => $this->admin->id,
            'type' => Notification::TYPE_PM_DUE,
        ]);
        $this->assertDatabaseHas('notifications', [
            'user_id' => $this->secondAdmin->id,
            'type' => Notification::TYPE_PM_DUE,
        ]);
        $this->assertDatabaseHas('notifications', [
            'user_id' => $this->driver->id,
            'type' => Notification::TYPE_PM_REMINDER,
        ]);
    }

    /** A schedule created far from its threshold is not news. */
    public function test_creating_an_upcoming_schedule_alerts_nobody(): void
    {
        $this->actingAs($this->admin)->from('/pm')
            ->post('/pm', $this->overduePayload([
                'last_pm_mileage' => 49_000,
                'interval_km' => 20_000, // due at 69 000; vehicle is at 50 000
            ]))
            ->assertSessionHasNoErrors();

        $this->assertDatabaseCount('notifications', 0);
    }

    /** The immediate path and the daily sweep must not both alert. */
    public function test_the_daily_command_does_not_re_alert_what_the_form_already_raised(): void
    {
        $this->actingAs($this->admin)->from('/pm')->post('/pm', $this->overduePayload());
        $this->assertDatabaseCount('notifications', 3);

        $this->artisan('rvms:pm-alerts')->assertSuccessful();

        $this->assertDatabaseCount('notifications', 3); // not 6
    }

    /** Editing a schedule INTO a due state alerts too. */
    public function test_editing_a_schedule_into_a_due_state_alerts(): void
    {
        $this->actingAs($this->admin)->from('/pm')->post('/pm', $this->overduePayload([
            'last_pm_mileage' => 49_000,
            'interval_km' => 20_000,
        ]));
        $this->assertDatabaseCount('notifications', 0);

        $schedule = PmSchedule::withoutGlobalScopes()->firstOrFail();

        $this->actingAs($this->admin)->from('/pm')
            ->put("/pm/{$schedule->id}", $this->overduePayload())
            ->assertSessionHasNoErrors();

        $this->assertDatabaseCount('notifications', 3);
    }

    public function test_the_api_also_alerts_on_an_already_due_schedule(): void
    {
        $this->actingAs($this->admin, 'sanctum')
            ->postJson('/api/v1/pm-schedules', $this->overduePayload())
            ->assertCreated();

        $this->assertDatabaseCount('notifications', 3);
    }

    /* --------------------- licence recorded already expiring -------------- */

    private function driverPayload(array $overrides = []): array
    {
        return array_merge([
            'name' => 'Bagong Drayber',
            'email' => 'bagong@rvms.local',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'license_number' => 'N01-11-222222',
            'license_expiry_date' => now()->addDays(10)->toDateString(),
        ], $overrides);
    }

    public function test_adding_a_driver_with_an_expiring_licence_alerts_immediately(): void
    {
        $this->actingAs($this->admin)->from('/drivers')
            ->post('/drivers', $this->driverPayload())
            ->assertSessionHasNoErrors();

        // No command was run. Both admins.
        $this->assertDatabaseCount('notifications', 2);
        foreach ([$this->admin, $this->secondAdmin] as $recipient) {
            $this->assertDatabaseHas('notifications', [
                'user_id' => $recipient->id,
                'type' => Notification::TYPE_LICENSE_EXPIRING,
            ]);
        }
    }

    public function test_adding_a_driver_with_an_expired_licence_uses_the_expired_type(): void
    {
        $this->actingAs($this->admin)->from('/drivers')
            ->post('/drivers', $this->driverPayload([
                'license_expiry_date' => now()->subDays(5)->toDateString(),
            ]))
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('notifications', [
            'user_id' => $this->admin->id,
            'type' => Notification::TYPE_LICENSE_EXPIRED,
        ]);
    }

    public function test_adding_a_driver_with_a_valid_licence_alerts_nobody(): void
    {
        $this->actingAs($this->admin)->from('/drivers')
            ->post('/drivers', $this->driverPayload([
                'license_expiry_date' => now()->addMonths(8)->toDateString(),
            ]))
            ->assertSessionHasNoErrors();

        $this->assertDatabaseCount('notifications', 0);
    }

    /** Editing a driver's expiry INTO the window alerts. */
    public function test_editing_a_licence_into_the_warning_window_alerts(): void
    {
        $this->driver->update(['license_expiry_date' => now()->addYear()->toDateString()]);

        $this->actingAs($this->admin)->from('/drivers')
            ->put("/drivers/{$this->driver->id}", [
                'name' => $this->driver->name,
                'email' => $this->driver->email,
                'license_number' => $this->driver->license_number,
                'license_expiry_date' => now()->addDays(7)->toDateString(),
            ])
            ->assertSessionHasNoErrors();

        $this->assertDatabaseCount('notifications', 2);
    }

    public function test_the_daily_licence_command_does_not_re_alert(): void
    {
        $this->actingAs($this->admin)->from('/drivers')->post('/drivers', $this->driverPayload());
        $this->assertDatabaseCount('notifications', 2);

        $this->artisan('rvms:license-alerts')->assertSuccessful();

        $this->assertDatabaseCount('notifications', 2); // not 4
    }

    /** The window is still the agency's own configurable column, not a constant. */
    public function test_the_immediate_path_honours_the_agency_threshold(): void
    {
        $this->actingAs($this->admin)->from('/drivers')
            ->post('/drivers', $this->driverPayload([
                'email' => 'far@rvms.local',
                'license_expiry_date' => now()->addDays(45)->toDateString(),
            ]));
        $this->assertDatabaseCount('notifications', 0); // outside a 30-day window

        $this->agency->update(['license_expiry_warning_days' => 60]);

        $this->actingAs($this->admin)->from('/drivers')
            ->post('/drivers', $this->driverPayload([
                'email' => 'near@rvms.local',
                'license_number' => 'N01-33-444444',
                'license_expiry_date' => now()->addDays(45)->toDateString(),
            ]));
        $this->assertDatabaseCount('notifications', 2); // inside a 60-day window
    }

    public function test_alerts_never_cross_agencies(): void
    {
        $other = Agency::factory()->create(['code' => 'PNP']);
        $otherAdmin = User::factory()->admin()->create(['agency_id' => $other->id]);

        $this->actingAs($this->admin)->from('/drivers')->post('/drivers', $this->driverPayload());

        $this->assertDatabaseMissing('notifications', ['user_id' => $otherAdmin->id]);
    }
}
