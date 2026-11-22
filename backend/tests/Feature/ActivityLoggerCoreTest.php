<?php

namespace Tests\Feature;

use App\Models\ActivityLog;
use App\Models\Agency;
use App\Models\User;
use App\Models\Vehicle;
use App\Services\ActivityLogger;
use App\Services\VehicleStatusWriter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class ActivityLoggerCoreTest extends TestCase
{
    use RefreshDatabase;

    private Agency $bfp;

    private Agency $cho;

    private User $bfpAdmin;

    private User $choDriver;

    private ActivityLogger $logger;

    protected function setUp(): void
    {
        parent::setUp();

        $this->bfp = Agency::factory()->create(['code' => 'BFP']);
        $this->cho = Agency::factory()->create(['code' => 'CHO']);

        $this->bfpAdmin = User::factory()->admin()->create([
            'agency_id' => $this->bfp->id,
            'name' => 'BFP Admin',
            'email' => 'bfp.admin@rvms.local',
        ]);

        $this->choDriver = User::factory()->driver()->create([
            'agency_id' => $this->cho->id,
            'name' => 'CHO Driver',
            'email' => 'cho.driver@rvms.local',
        ]);

        $this->logger = app(ActivityLogger::class);
    }

    private function makeRequest(string $uri = '/login', string $method = 'POST'): Request
    {
        return Request::create($uri, $method, [], [], [], [
            'REMOTE_ADDR' => '203.0.113.42',
            'HTTP_USER_AGENT' => 'RVMS-Test-Agent/1.0',
        ]);
    }

    public function test_it_records_successful_login(): void
    {
        $log = $this->logger->logLoginSuccess(
            $this->makeRequest('/login'),
            $this->bfpAdmin,
            ActivityLog::SOURCE_WEB,
        );

        $this->assertNotNull($log);
        $this->assertSame($this->bfp->id, $log->agency_id);
        $this->assertSame($this->bfpAdmin->id, $log->user_id);
        $this->assertSame(ActivityLog::TYPE_LOGIN_SUCCESS, $log->event_type);
        $this->assertSame(ActivityLog::SEVERITY_INFO, $log->severity);
        $this->assertSame(ActivityLog::SOURCE_WEB, $log->source);
        $this->assertSame('203.0.113.42', $log->ip_address);
        $this->assertSame('RVMS-Test-Agent/1.0', $log->user_agent);
        $this->assertSame('Successful Login', $log->eventLabel());
        $this->assertSame('Info', $log->severityLabel());
        $this->assertFalse($log->isUnrecognizedAccount());
    }

    public function test_it_records_failed_login_for_known_and_unrecognized_accounts(): void
    {
        $known = $this->logger->logLoginFailed(
            $this->makeRequest('/login'),
            '  BFP.Admin@rvms.local ',
            ActivityLog::SOURCE_WEB,
            'Invalid credentials',
        );

        $this->assertNotNull($known);
        $this->assertSame($this->bfp->id, $known->agency_id);
        $this->assertSame($this->bfpAdmin->id, $known->user_id);
        $this->assertSame('bfp.admin@rvms.local', $known->actor_email);
        $this->assertSame(ActivityLog::TYPE_LOGIN_FAILED, $known->event_type);
        $this->assertSame(ActivityLog::SEVERITY_WARNING, $known->severity);
        $this->assertTrue($known->metadata['account_recognized']);
        $this->assertFalse($known->isUnrecognizedAccount());

        $unknown = $this->logger->logLoginFailed(
            $this->makeRequest('/api/v1/login'),
            'attacker@unknown.test',
            ActivityLog::SOURCE_MOBILE_API,
            'Unrecognized account',
        );

        $this->assertNotNull($unknown);
        $this->assertNull($unknown->agency_id);
        $this->assertNull($unknown->user_id);
        $this->assertNull($unknown->actor_name);
        $this->assertSame('unknown', $unknown->actor_role);
        $this->assertSame('attacker@unknown.test', $unknown->actor_email);
        $this->assertSame(ActivityLog::SOURCE_MOBILE_API, $unknown->source);
        $this->assertFalse($unknown->metadata['account_recognized']);
        $this->assertTrue($unknown->isUnrecognizedAccount());
    }

    public function test_it_records_logout(): void
    {
        $log = $this->logger->logLogout(
            $this->makeRequest('/api/v1/logout'),
            $this->choDriver,
            ActivityLog::SOURCE_MOBILE_API,
        );

        $this->assertNotNull($log);
        $this->assertSame($this->cho->id, $log->agency_id);
        $this->assertSame($this->choDriver->id, $log->user_id);
        $this->assertSame(ActivityLog::TYPE_LOGOUT, $log->event_type);
        $this->assertSame(ActivityLog::SEVERITY_INFO, $log->severity);
        $this->assertSame(ActivityLog::SOURCE_MOBILE_API, $log->source);
        $this->assertSame('Logout', $log->eventLabel());
    }

    public function test_it_records_vehicle_status_updates_with_contextual_severity(): void
    {
        $vehicle = Vehicle::factory()->create([
            'agency_id' => $this->bfp->id,
            'plate_number' => 'BFP-101',
            'type' => 'Fire Truck',
            'status' => Vehicle::STATUS_OPERATIONAL,
            'remarks' => 'Brake line leak reported',
        ]);

        $warningLog = $this->logger->logVehicleStatusUpdated(
            $vehicle,
            Vehicle::STATUS_OPERATIONAL,
            Vehicle::STATUS_NOT_OPERATIONAL,
            VehicleStatusWriter::SOURCE_DAMAGE,
            $this->bfpAdmin,
            $this->makeRequest('/damage-reports/1/review', 'PATCH'),
        );

        $this->assertNotNull($warningLog);
        $this->assertSame($this->bfp->id, $warningLog->agency_id);
        $this->assertSame($this->bfpAdmin->id, $warningLog->user_id);
        $this->assertSame(ActivityLog::TYPE_VEHICLE_STATUS_UPDATED, $warningLog->event_type);
        $this->assertSame(ActivityLog::SEVERITY_WARNING, $warningLog->severity);
        $this->assertSame(ActivityLog::SOURCE_WEB, $warningLog->source);
        $this->assertSame('BFP-101 (Fire Truck)', $warningLog->target_label);
        $this->assertSame('Operational', $warningLog->metadata['from_status']);
        $this->assertSame('Not Operational', $warningLog->metadata['to_status']);
        $this->assertSame(VehicleStatusWriter::SOURCE_DAMAGE, $warningLog->metadata['module']);

        $infoLog = $this->logger->logVehicleStatusUpdated(
            $vehicle,
            Vehicle::STATUS_NOT_OPERATIONAL,
            Vehicle::STATUS_OPERATIONAL,
            VehicleStatusWriter::SOURCE_REPAIR,
            $this->bfpAdmin,
            $this->makeRequest('/api/v1/vehicles/1/status', 'PATCH'),
        );

        $this->assertNotNull($infoLog);
        $this->assertSame(ActivityLog::SEVERITY_INFO, $infoLog->severity);
        $this->assertSame(ActivityLog::SOURCE_MOBILE_API, $infoLog->source);
    }

    public function test_visible_to_agency_includes_own_and_unrecognized_events_and_excludes_foreign_agency(): void
    {
        $bfpLog = $this->logger->logLoginSuccess($this->makeRequest(), $this->bfpAdmin, ActivityLog::SOURCE_WEB);
        $choLog = $this->logger->logLoginSuccess($this->makeRequest(), $this->choDriver, ActivityLog::SOURCE_MOBILE_API);
        $probeLog = $this->logger->logLoginFailed($this->makeRequest(), 'probe@example.com', ActivityLog::SOURCE_WEB, 'Unrecognized account');

        $visibleIds = ActivityLog::query()
            ->visibleToAgency($this->bfp->id)
            ->pluck('id')
            ->all();

        $this->assertContains($bfpLog->id, $visibleIds);
        $this->assertContains($probeLog->id, $visibleIds);
        $this->assertNotContains($choLog->id, $visibleIds);
    }

    public function test_sanitize_metadata_strips_sensitive_keys_recursively(): void
    {
        $sanitized = ActivityLogger::sanitizeMetadata([
            'reason' => 'Invalid credentials',
            'password' => 'super-secret',
            'fcm_token' => 'device-token-123',
            'nested' => [
                'current_password' => 'old-pass',
                'authorization' => 'Bearer abc',
                'cookie' => 'rvms_session=xyz',
                'api_secret' => 'hidden',
                'safe_note' => 'keep me',
            ],
        ]);

        $this->assertSame([
            'reason' => 'Invalid credentials',
            'nested' => [
                'safe_note' => 'keep me',
            ],
        ], $sanitized);
    }

    public function test_timestamp_label_formats_in_asia_manila(): void
    {
        $this->assertSame('Asia/Manila', config('app.timezone'));

        // Persisted early-morning Manila timestamp stays in Asia/Manila across DB roundtrip.
        $log = $this->logger->logLoginSuccess($this->makeRequest(), $this->bfpAdmin, ActivityLog::SOURCE_WEB);
        $log->forceFill([
            'created_at' => Carbon::create(2026, 11, 20, 7, 30, 5, 'Asia/Manila'),
        ])->save();

        $this->assertSame('Nov 20, 2026 · 7:30:05 AM', $log->fresh()->timestampLabel());
        $this->assertSame('—', (new ActivityLog)->timestampLabel());
    }
}
