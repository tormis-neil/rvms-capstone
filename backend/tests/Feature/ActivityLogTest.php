<?php

namespace Tests\Feature;

use App\Models\ActivityLog;
use App\Models\Agency;
use App\Models\Dispatch;
use App\Models\User;
use App\Models\Vehicle;
use App\Services\ActivityLogger;
use App\Services\VehicleStatusWriter;
use App\Support\LoginThrottle;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/**
 * End-to-end verification of activity log instrumentation across Web Portal
 * authentication, Mobile API authentication, and VehicleStatusWriter.
 */
class ActivityLogTest extends TestCase
{
    use RefreshDatabase;

    private Agency $bfp;

    private User $admin;

    private User $driver;

    private string $secret = 'SecretPass99!';

    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();

        $this->bfp = Agency::factory()->create(['code' => 'BFP']);

        $this->admin = User::factory()->admin()->create([
            'agency_id' => $this->bfp->id,
            'name' => 'BFP Admin',
            'email' => 'bfp.admin@rvms.local',
            'password' => $this->secret,
        ]);

        $this->driver = User::factory()->driver()->create([
            'agency_id' => $this->bfp->id,
            'name' => 'BFP Driver',
            'email' => 'bfp.driver@rvms.local',
            'password' => $this->secret,
        ]);
    }

    public function test_web_login_success_and_logout_are_logged_without_storing_passwords(): void
    {
        $this->post('/login', [
            'email' => $this->admin->email,
            'password' => $this->secret,
        ])->assertRedirect(route('dashboard'));

        $loginLog = ActivityLog::query()
            ->where('event_type', ActivityLog::TYPE_LOGIN_SUCCESS)
            ->first();

        $this->assertNotNull($loginLog);
        $this->assertSame($this->bfp->id, $loginLog->agency_id);
        $this->assertSame($this->admin->id, $loginLog->user_id);
        $this->assertSame(ActivityLog::SOURCE_WEB, $loginLog->source);
        $this->assertSame(ActivityLog::SEVERITY_INFO, $loginLog->severity);
        $this->assertSame('admin', $loginLog->actor_role);

        $this->post('/logout')->assertRedirect(route('login'));

        $logoutLog = ActivityLog::query()
            ->where('event_type', ActivityLog::TYPE_LOGOUT)
            ->first();

        $this->assertNotNull($logoutLog);
        $this->assertSame($this->admin->id, $logoutLog->user_id);
        $this->assertSame(ActivityLog::SOURCE_WEB, $logoutLog->source);
        $this->assertSame(ActivityLog::SEVERITY_INFO, $logoutLog->severity);

        $this->assertSecretNeverLogged($this->secret);
    }

    public function test_web_failed_logins_record_known_unrecognized_and_driver_restricted_attempts(): void
    {
        $this->from('/login')->post('/login', [
            'email' => $this->admin->email,
            'password' => 'wrong-password-1',
        ])->assertSessionHasErrors('email');

        $knownFailure = ActivityLog::query()->latest('id')->first();
        $this->assertSame(ActivityLog::TYPE_LOGIN_FAILED, $knownFailure->event_type);
        $this->assertSame(ActivityLog::SEVERITY_WARNING, $knownFailure->severity);
        $this->assertSame(ActivityLog::SOURCE_WEB, $knownFailure->source);
        $this->assertSame($this->bfp->id, $knownFailure->agency_id);
        $this->assertSame($this->admin->id, $knownFailure->user_id);
        $this->assertSame('Invalid credentials', $knownFailure->metadata['reason']);
        $this->assertTrue($knownFailure->metadata['account_recognized']);

        $this->from('/login')->post('/login', [
            'email' => 'intruder@unknown.local',
            'password' => 'wrong-password-2',
        ])->assertSessionHasErrors('email');

        $unknownFailure = ActivityLog::query()->latest('id')->first();
        $this->assertSame(ActivityLog::TYPE_LOGIN_FAILED, $unknownFailure->event_type);
        $this->assertNull($unknownFailure->agency_id);
        $this->assertNull($unknownFailure->user_id);
        $this->assertSame('unknown', $unknownFailure->actor_role);
        $this->assertSame('intruder@unknown.local', $unknownFailure->actor_email);
        $this->assertFalse($unknownFailure->metadata['account_recognized']);

        $this->from('/login')->post('/login', [
            'email' => $this->driver->email,
            'password' => $this->secret,
        ])->assertSessionHasErrors('email');

        $driverFailure = ActivityLog::query()->latest('id')->first();
        $this->assertSame(ActivityLog::TYPE_LOGIN_FAILED, $driverFailure->event_type);
        $this->assertSame($this->bfp->id, $driverFailure->agency_id);
        $this->assertSame($this->driver->id, $driverFailure->user_id);
        $this->assertSame('driver', $driverFailure->actor_role);
        $this->assertStringContainsString('restricted', $driverFailure->metadata['reason']);

        $this->assertSecretNeverLogged($this->secret);
        $this->assertSecretNeverLogged('wrong-password-1');
        $this->assertSecretNeverLogged('wrong-password-2');
    }

    public function test_web_login_throttle_still_blocks_without_recording_lockout_events(): void
    {
        for ($i = 0; $i < LoginThrottle::MAX_ATTEMPTS; $i++) {
            $this->from('/login')->post('/login', [
                'email' => 'hacker@probe.local',
                'password' => 'bad-guess',
            ])->assertSessionHasErrors('email');
        }

        $this->from('/login')->post('/login', [
            'email' => 'hacker@probe.local',
            'password' => 'bad-guess',
        ])->assertSessionHasErrors('email');

        $this->assertSame(
            0,
            ActivityLog::query()->where('event_type', 'login_lockout')->count()
        );
        $this->assertSame(
            LoginThrottle::MAX_ATTEMPTS,
            ActivityLog::query()
                ->where('event_type', ActivityLog::TYPE_LOGIN_FAILED)
                ->where('actor_email', 'hacker@probe.local')
                ->count()
        );

        for ($i = 0; $i < LoginThrottle::MAX_ATTEMPTS; $i++) {
            $this->from('/login')->post('/login', [
                'email' => $this->admin->email,
                'password' => 'bad-guess',
            ])->assertSessionHasErrors('email');
        }

        $this->from('/login')->post('/login', [
            'email' => $this->admin->email,
            'password' => $this->secret,
        ])->assertSessionHasErrors('email');

        $this->assertFalse(auth()->check());
        $this->assertSame(
            0,
            ActivityLog::query()->where('event_type', 'login_lockout')->count()
        );
        $this->assertSame(
            LoginThrottle::MAX_ATTEMPTS,
            ActivityLog::query()
                ->where('event_type', ActivityLog::TYPE_LOGIN_FAILED)
                ->where('actor_email', $this->admin->email)
                ->count()
        );

        $this->assertSecretNeverLogged($this->secret);
        $this->assertSecretNeverLogged('bad-guess');
    }

    public function test_mobile_api_auth_events_are_logged_without_storing_tokens_or_passwords(): void
    {
        $this->postJson('/api/v1/login', [
            'email' => $this->driver->email,
            'password' => 'wrong-api-pass',
        ])->assertStatus(422);

        $wrongPassLog = ActivityLog::query()->latest('id')->first();
        $this->assertSame(ActivityLog::TYPE_LOGIN_FAILED, $wrongPassLog->event_type);
        $this->assertSame(ActivityLog::SOURCE_MOBILE_API, $wrongPassLog->source);
        $this->assertSame('Invalid password', $wrongPassLog->metadata['reason']);

        $this->postJson('/api/v1/login', [
            'email' => 'ghost@mobile.local',
            'password' => 'wrong-api-pass',
        ])->assertStatus(422);

        $unrecognizedLog = ActivityLog::query()->latest('id')->first();
        $this->assertSame(ActivityLog::TYPE_LOGIN_FAILED, $unrecognizedLog->event_type);
        $this->assertSame(ActivityLog::SOURCE_MOBILE_API, $unrecognizedLog->source);
        $this->assertNull($unrecognizedLog->agency_id);
        $this->assertSame('Unrecognized account', $unrecognizedLog->metadata['reason']);

        $pending = User::factory()->driver()->pending()->create([
            'agency_id' => $this->bfp->id,
            'email' => 'pending.api@rvms.local',
            'password' => $this->secret,
        ]);

        $this->postJson('/api/v1/login', [
            'email' => $pending->email,
            'password' => $this->secret,
        ])->assertStatus(403);

        $pendingLog = ActivityLog::query()->latest('id')->first();
        $this->assertSame(ActivityLog::TYPE_LOGIN_FAILED, $pendingLog->event_type);
        $this->assertSame($pending->id, $pendingLog->user_id);
        $this->assertSame('Account status is pending', $pendingLog->metadata['reason']);

        for ($i = 0; $i < LoginThrottle::MAX_ATTEMPTS; $i++) {
            $this->postJson('/api/v1/login', [
                'email' => 'mobile.lockout@rvms.local',
                'password' => 'bad',
            ])->assertStatus(422);
        }

        $this->postJson('/api/v1/login', [
            'email' => 'mobile.lockout@rvms.local',
            'password' => 'bad',
        ])->assertStatus(429);

        $this->assertSame(
            0,
            ActivityLog::query()->where('event_type', 'login_lockout')->count()
        );
        $this->assertSame(
            LoginThrottle::MAX_ATTEMPTS,
            ActivityLog::query()
                ->where('event_type', ActivityLog::TYPE_LOGIN_FAILED)
                ->where('actor_email', 'mobile.lockout@rvms.local')
                ->where('source', ActivityLog::SOURCE_MOBILE_API)
                ->count()
        );

        $loginResponse = $this->postJson('/api/v1/login', [
            'email' => $this->driver->email,
            'password' => $this->secret,
        ])->assertOk();

        $token = (string) $loginResponse->json('token');

        $apiSuccess = ActivityLog::query()
            ->where('event_type', ActivityLog::TYPE_LOGIN_SUCCESS)
            ->where('user_id', $this->driver->id)
            ->first();

        $this->assertNotNull($apiSuccess);
        $this->assertSame(ActivityLog::SOURCE_MOBILE_API, $apiSuccess->source);

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson('/api/v1/logout')
            ->assertOk();

        $apiLogout = ActivityLog::query()
            ->where('event_type', ActivityLog::TYPE_LOGOUT)
            ->where('user_id', $this->driver->id)
            ->first();

        $this->assertNotNull($apiLogout);
        $this->assertSame(ActivityLog::SOURCE_MOBILE_API, $apiLogout->source);

        $this->assertSecretNeverLogged($this->secret);
        $this->assertSecretNeverLogged($token);
    }

    public function test_vehicle_status_updates_from_web_api_and_dispatch_are_logged_and_idempotent_writes_are_skipped(): void
    {
        $vehicle = Vehicle::factory()->create([
            'agency_id' => $this->bfp->id,
            'plate_number' => 'BFP-202',
            'type' => 'Fire Truck',
            'status' => Vehicle::STATUS_OPERATIONAL,
        ]);

        // 1. Web status change -> Not Operational (Warning)
        $this->actingAs($this->admin)
            ->patch("/vehicles/{$vehicle->id}/status", [
                'status' => Vehicle::STATUS_NOT_OPERATIONAL,
                'remarks' => 'Engine warning light active',
            ])
            ->assertRedirect(route('vehicles'));

        $webChange = ActivityLog::query()
            ->where('event_type', ActivityLog::TYPE_VEHICLE_STATUS_UPDATED)
            ->latest('id')
            ->first();

        $this->assertNotNull($webChange);
        $this->assertSame($this->bfp->id, $webChange->agency_id);
        $this->assertSame($this->admin->id, $webChange->user_id);
        $this->assertSame(ActivityLog::SOURCE_WEB, $webChange->source);
        $this->assertSame(ActivityLog::SEVERITY_WARNING, $webChange->severity);
        $this->assertSame('Operational', $webChange->metadata['from_status']);
        $this->assertSame('Not Operational', $webChange->metadata['to_status']);
        $this->assertSame(VehicleStatusWriter::SOURCE_VEHICLES, $webChange->metadata['module']);
        $this->assertSame('Engine warning light active', $webChange->metadata['remarks']);

        // 2. Idempotent re-save of same status creates NO new log
        $this->actingAs($this->admin)
            ->patch("/vehicles/{$vehicle->id}/status", [
                'status' => Vehicle::STATUS_NOT_OPERATIONAL,
                'remarks' => 'Still awaiting parts',
            ])
            ->assertRedirect(route('vehicles'));

        $this->assertSame(
            1,
            ActivityLog::query()->where('event_type', ActivityLog::TYPE_VEHICLE_STATUS_UPDATED)->count()
        );

        // 3. API status change -> Operational (Info, Mobile API)
        Sanctum::actingAs($this->admin);
        $this->patchJson("/api/v1/vehicles/{$vehicle->id}/status", [
            'status' => Vehicle::STATUS_OPERATIONAL,
            'remarks' => 'Cleared by mechanic',
        ])->assertOk();

        $apiChange = ActivityLog::query()
            ->where('event_type', ActivityLog::TYPE_VEHICLE_STATUS_UPDATED)
            ->latest('id')
            ->first();

        $this->assertSame(ActivityLog::SOURCE_MOBILE_API, $apiChange->source);
        $this->assertSame(ActivityLog::SEVERITY_INFO, $apiChange->severity);
        $this->assertSame('Not Operational', $apiChange->metadata['from_status']);
        $this->assertSame('Operational', $apiChange->metadata['to_status']);

        // 4. Dispatch open + close
        $this->actingAs($this->admin)->post('/dispatch', [
            'vehicle_id' => $vehicle->id,
            'driver_id' => $this->driver->id,
            'mission_type' => 'Fire Response',
            'location' => 'Brgy. Central',
            'time_out' => now()->toDateTimeString(),
        ])->assertRedirect(route('dispatch'));

        $dispatchOpenLog = ActivityLog::query()
            ->where('event_type', ActivityLog::TYPE_VEHICLE_STATUS_UPDATED)
            ->latest('id')
            ->first();

        $this->assertSame('Operational', $dispatchOpenLog->metadata['from_status']);
        $this->assertSame('Dispatched', $dispatchOpenLog->metadata['to_status']);
        $this->assertSame(VehicleStatusWriter::SOURCE_DISPATCH_OPEN, $dispatchOpenLog->metadata['module']);
        $this->assertSame(ActivityLog::SEVERITY_INFO, $dispatchOpenLog->severity);

        $dispatch = Dispatch::withoutGlobalScopes()->latest('id')->first();

        $this->actingAs($this->admin)->patch("/dispatch/{$dispatch->id}/close", [
            'time_in' => now()->toDateTimeString(),
            'return_status' => Vehicle::STATUS_OPERATIONAL,
        ])->assertRedirect(route('dispatch'));

        $dispatchCloseLog = ActivityLog::query()
            ->where('event_type', ActivityLog::TYPE_VEHICLE_STATUS_UPDATED)
            ->latest('id')
            ->first();

        $this->assertSame('Dispatched', $dispatchCloseLog->metadata['from_status']);
        $this->assertSame('Operational', $dispatchCloseLog->metadata['to_status']);
        $this->assertSame(VehicleStatusWriter::SOURCE_DISPATCH_CLOSE, $dispatchCloseLog->metadata['module']);
    }

    public function test_system_monitor_access_control_and_read_only_immutability(): void
    {
        $this->get('/activity-logs')->assertRedirect(route('login'));

        $this->actingAs($this->driver)
            ->get('/activity-logs')
            ->assertForbidden();

        $this->actingAs($this->admin)
            ->get('/activity-logs')
            ->assertOk()
            ->assertSee('System Monitor')
            ->assertSee('EVENTS TODAY')
            ->assertSee('SUCCESSFUL LOGINS')
            ->assertSee('FAILED LOGINS')
            ->assertDontSee('LOCKOUTS')
            ->assertSee('VEHICLE STATUS UPDATES')
            ->assertSee('id="viewActivityLogModal"', false);

        $this->actingAs($this->admin)->delete('/activity-logs')->assertStatus(405);
        $this->actingAs($this->admin)->delete('/activity-logs/1')->assertStatus(404);
    }

    public function test_system_monitor_enforces_agency_isolation_while_showing_unrecognized_accounts(): void
    {
        $cho = Agency::factory()->create(['code' => 'CHO']);
        $choAdmin = User::factory()->admin()->create([
            'agency_id' => $cho->id,
            'name' => 'CHO Admin',
            'email' => 'cho.admin@rvms.local',
        ]);

        $logger = app(ActivityLogger::class);
        $request = Request::create('/login', 'POST', [], [], [], ['REMOTE_ADDR' => '192.0.2.10']);

        $logger->logLoginSuccess($request, $this->admin, ActivityLog::SOURCE_WEB);
        $logger->logLoginSuccess($request, $choAdmin, ActivityLog::SOURCE_MOBILE_API);
        $logger->logLoginFailed($request, 'probe@attacker.local', ActivityLog::SOURCE_WEB, 'Invalid credentials');

        ActivityLog::query()->create([
            'agency_id' => $this->bfp->id,
            'actor_email' => 'legacy.lockout@rvms.local',
            'actor_role' => User::ROLE_ADMIN,
            'event_type' => 'login_lockout',
            'severity' => 'high',
            'source' => ActivityLog::SOURCE_WEB,
            'description' => 'Legacy lockout row',
        ]);

        $response = $this->actingAs($this->admin)
            ->get('/activity-logs')
            ->assertOk();

        $response->assertSee($this->admin->email)
            ->assertSee('Unrecognized Account')
            ->assertSee('probe@attacker.local')
            ->assertDontSee('cho.admin@rvms.local')
            ->assertDontSee('CHO Admin')
            ->assertDontSee('legacy.lockout@rvms.local');

        $summary = $response->viewData('summary');
        $this->assertSame(2, $summary['events_today']);
        $this->assertSame(1, $summary['successful_logins']);
        $this->assertSame(1, $summary['failed_logins']);
        $this->assertSame(0, $summary['vehicle_status_updates']);

        $choResponse = $this->actingAs($choAdmin)
            ->get('/activity-logs')
            ->assertOk();

        $choResponse->assertSee('cho.admin@rvms.local')
            ->assertSee('Unrecognized Account')
            ->assertSee('probe@attacker.local')
            ->assertDontSee($this->admin->email)
            ->assertDontSee('legacy.lockout@rvms.local');
    }

    public function test_system_monitor_filters_and_searches_safely(): void
    {
        $logger = app(ActivityLogger::class);

        $logger->logLoginSuccess(
            Request::create('/login', 'POST', [], [], [], ['REMOTE_ADDR' => '10.0.0.1']),
            $this->admin,
            ActivityLog::SOURCE_WEB,
        );

        $logger->logLoginFailed(
            Request::create('/api/v1/login', 'POST', [], [], [], ['REMOTE_ADDR' => '10.0.0.2']),
            $this->driver->email,
            ActivityLog::SOURCE_MOBILE_API,
            'Invalid password',
            $this->driver,
        );

        $logger->logLogout(
            Request::create('/logout', 'POST', [], [], [], ['REMOTE_ADDR' => '198.51.100.77']),
            $this->admin,
            ActivityLog::SOURCE_WEB,
        );

        $vehicle = Vehicle::factory()->create([
            'agency_id' => $this->bfp->id,
            'plate_number' => 'BFP-999',
            'type' => 'Fire Truck',
            'status' => Vehicle::STATUS_NOT_OPERATIONAL,
            'remarks' => 'Pump seal leak',
        ]);

        $logger->logVehicleStatusUpdated(
            $vehicle,
            Vehicle::STATUS_OPERATIONAL,
            Vehicle::STATUS_NOT_OPERATIONAL,
            VehicleStatusWriter::SOURCE_VEHICLES,
            $this->admin,
            Request::create('/vehicles/'.$vehicle->id.'/status', 'PATCH', [], [], [], ['REMOTE_ADDR' => '10.0.0.3']),
        );

        ActivityLog::query()->create([
            'agency_id' => $this->bfp->id,
            'actor_email' => 'legacy@probe.local',
            'actor_role' => 'unknown',
            'event_type' => 'login_lockout',
            'severity' => 'high',
            'source' => ActivityLog::SOURCE_WEB,
            'description' => 'Legacy lockout event',
        ]);

        $this->actingAs($this->admin)
            ->get('/activity-logs?event_type=logout')
            ->assertOk()
            ->assertSee('198.51.100.77')
            ->assertDontSee('BFP-999')
            ->assertDontSee($this->driver->email)
            ->assertDontSee('10.0.0.1')
            ->assertDontSee('legacy@probe.local');

        $this->actingAs($this->admin)
            ->get('/activity-logs?severity=warning')
            ->assertOk()
            ->assertSee($this->driver->email)
            ->assertSee('BFP-999')
            ->assertDontSee('198.51.100.77')
            ->assertDontSee('10.0.0.1')
            ->assertDontSee('legacy@probe.local');

        $this->actingAs($this->admin)
            ->get('/activity-logs?source=Mobile+API')
            ->assertOk()
            ->assertSee($this->driver->email)
            ->assertDontSee('198.51.100.77')
            ->assertDontSee('BFP-999');

        $this->actingAs($this->admin)
            ->get('/activity-logs?search=BFP-999')
            ->assertOk()
            ->assertSee('BFP-999')
            ->assertDontSee('198.51.100.77');

        $this->actingAs($this->admin)
            ->get('/activity-logs?search=198.51.100.77')
            ->assertOk()
            ->assertSee('198.51.100.77')
            ->assertDontSee('BFP-999');

        $this->actingAs($this->admin)
            ->get('/activity-logs?event_type=login_lockout&severity=high&source=unknown')
            ->assertOk()
            ->assertSee('Showing 1 to 4 of 4 events')
            ->assertDontSee('legacy@probe.local');
    }

    public function test_system_monitor_paginates_at_fifteen_with_constant_query_count(): void
    {
        $logger = app(ActivityLogger::class);
        $request = Request::create('/login', 'POST', [], [], [], ['REMOTE_ADDR' => '127.0.0.1']);

        for ($i = 0; $i < 2; $i++) {
            $logger->logLoginSuccess($request, $this->admin, ActivityLog::SOURCE_WEB);
        }

        $smallQueryCount = $this->countMonitorQueries();

        for ($i = 0; $i < 18; $i++) {
            $logger->logLoginSuccess($request, $this->admin, ActivityLog::SOURCE_WEB);
        }

        $largeQueryCount = $this->countMonitorQueries();

        $this->assertSame(
            $smallQueryCount,
            $largeQueryCount,
            "System Monitor query count grew from {$smallQueryCount} to {$largeQueryCount} as logs scaled."
        );

        $this->actingAs($this->admin)
            ->get('/activity-logs')
            ->assertOk()
            ->assertSee('Showing 1 to 15 of 20 events');

        $this->actingAs($this->admin)
            ->get('/activity-logs?page=2')
            ->assertOk()
            ->assertSee('Showing 16 to 20 of 20 events');
    }

    private function countMonitorQueries(): int
    {
        DB::flushQueryLog();
        DB::enableQueryLog();
        $this->actingAs($this->admin->fresh())->get('/activity-logs')->assertOk();
        $count = count(DB::getQueryLog());
        DB::disableQueryLog();

        return $count;
    }

    private function assertSecretNeverLogged(string $secret): void
    {
        foreach (ActivityLog::query()->get() as $log) {
            $encoded = json_encode($log->toArray(), JSON_THROW_ON_ERROR);
            $this->assertStringNotContainsString($secret, $encoded);
        }
    }
}
