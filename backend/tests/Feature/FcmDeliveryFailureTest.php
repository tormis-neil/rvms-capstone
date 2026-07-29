<?php

namespace Tests\Feature;

use App\Jobs\SendFcmMessage;
use App\Models\Agency;
use App\Models\User;
use App\Models\Vehicle;
use App\Services\Fcm\FcmConfigurationException;
use App\Services\Fcm\FcmHttpV1Transport;
use App\Services\Fcm\FcmMessage;
use App\Services\Fcm\FcmTransport;
use App\Services\Fcm\FcmTransportFactory;
use App\Services\Fcm\LogFcmTransport;
use App\Services\VehicleStatusWriter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use ReflectionProperty;
use RuntimeException;
use Tests\TestCase;

/**
 * What happens when a push does NOT go through (FR-21).
 *
 * The R7 manual test failed on exactly this: every queued push reported "FAIL"
 * and a duration, which is the same output whether the vendor folder was
 * stale, the service account belonged to another project, the machine could
 * not verify Google's certificate, or the handset had simply been reinstalled.
 * Each of those now has a distinct, named outcome — and, just as importantly,
 * only a genuinely dead device may cost a driver their registered token.
 */
class FcmDeliveryFailureTest extends TestCase
{
    use RefreshDatabase;

    private const ENDPOINT = 'https://fcm.googleapis.com/v1/projects/*';

    private FcmHttpV1Transport $transport;

    protected function setUp(): void
    {
        parent::setUp();

        // Seed the OAuth token so the transport never calls google/auth: these
        // tests are about how FCM's ANSWER is read, not about minting.
        Cache::put(FcmHttpV1Transport::CACHE_KEY, 'test-access-token', now()->addHour());

        $this->transport = new FcmHttpV1Transport('rvms-test', '/dev/null');
    }

    private function push(): FcmMessage
    {
        return new FcmMessage('device-abc', 'Title', 'Body', ['type' => 'Doctor']);
    }

    /** A handset that uninstalled the app: non-delivery, never an exception. */
    public function test_an_unregistered_device_is_a_non_delivery_not_a_failure(): void
    {
        Http::fake([self::ENDPOINT => Http::response([
            'error' => ['status' => 'UNREGISTERED', 'message' => 'Requested entity was not found.'],
        ], 404)]);

        $this->assertFalse($this->transport->send($this->push()));
    }

    /** A token minted by a different Firebase project is equally dead to us. */
    public function test_a_sender_id_mismatch_is_a_non_delivery(): void
    {
        Http::fake([self::ENDPOINT => Http::response([
            'error' => ['status' => 'SENDER_ID_MISMATCH', 'message' => 'SenderId mismatch'],
        ], 403)]);

        $this->assertFalse($this->transport->send($this->push()));
    }

    /** 400 naming the token is a dead device… */
    public function test_a_malformed_registration_token_is_a_non_delivery(): void
    {
        Http::fake([self::ENDPOINT => Http::response([
            'error' => [
                'status' => 'INVALID_ARGUMENT',
                'message' => 'The registration token is not a valid FCM registration token',
            ],
        ], 400)]);

        $this->assertFalse($this->transport->send($this->push()));
    }

    /**
     * …but 400 about anything else is OUR message being wrong, and must never
     * be mistaken for one. Clearing the driver's token over a payload bug
     * would silently unsubscribe a working phone.
     */
    public function test_a_rejected_payload_is_reported_as_a_configuration_fault(): void
    {
        Http::fake([self::ENDPOINT => Http::response([
            'error' => [
                'status' => 'INVALID_ARGUMENT',
                'message' => "Invalid JSON payload received. Unknown name \"foo\" at 'message'",
            ],
        ], 400)]);

        $this->expectException(FcmConfigurationException::class);
        $this->expectExceptionMessageMatches('/not a bad device token/');

        $this->transport->send($this->push());
    }

    /**
     * The single most likely live-Firebase mistake: the Cloud Messaging API is
     * switched off for the project. Previously this returned false and quietly
     * wiped every driver's token, one alert at a time.
     */
    public function test_permission_denied_names_the_project_and_never_clears_a_token(): void
    {
        Http::fake([self::ENDPOINT => Http::response([
            'error' => ['status' => 'PERMISSION_DENIED', 'message' => 'Firebase Cloud Messaging API has not been used'],
        ], 403)]);

        $this->expectException(FcmConfigurationException::class);
        $this->expectExceptionMessageMatches('/rvms-test/');

        $this->transport->send($this->push());
    }

    /** A refused access token must be dropped, or every retry repeats it. */
    public function test_a_refused_access_token_is_discarded_so_the_retry_mints_a_new_one(): void
    {
        Http::fake([self::ENDPOINT => Http::response([
            'error' => ['status' => 'UNAUTHENTICATED', 'message' => 'Request had invalid authentication credentials.'],
        ], 401)]);

        try {
            $this->transport->send($this->push());
            $this->fail('A 401 should have thrown.');
        } catch (RuntimeException $e) {
            $this->assertStringContainsString('401', $e->getMessage());
        }

        $this->assertFalse(Cache::has(FcmHttpV1Transport::CACHE_KEY));
    }

    /** A Google outage is transient and must stay retryable, not fail fast. */
    public function test_a_server_error_is_retryable_rather_than_a_configuration_fault(): void
    {
        Http::fake([self::ENDPOINT => Http::response('upstream unavailable', 503)]);

        try {
            $this->transport->send($this->push());
            $this->fail('A 503 should have thrown.');
        } catch (RuntimeException $e) {
            $this->assertNotInstanceOf(FcmConfigurationException::class, $e);
        }
    }

    /** FCM's parser rejects `"data": []`, so a data-less push must send `{}`. */
    public function test_an_empty_data_payload_encodes_as_an_object(): void
    {
        $payload = (new FcmMessage('device-abc', 'Title', 'Body'))->toPayload();

        $this->assertSame('{}', json_encode($payload['message']['data']));
    }

    /**
     * A misconfiguration fails identically on every attempt, so the job says
     * so once and stops instead of retrying into the same wall three times.
     */
    public function test_the_job_fails_fast_on_a_configuration_fault(): void
    {
        $agency = Agency::factory()->create(['code' => 'BFP']);
        $driver = User::factory()->driver()->create([
            'agency_id' => $agency->id,
            'fcm_token' => 'device-abc',
        ]);

        Http::fake([self::ENDPOINT => Http::response([
            'error' => ['status' => 'PERMISSION_DENIED', 'message' => 'API disabled'],
        ], 403)]);

        $this->app->instance(FcmTransport::class, $this->transport);

        // No exception escapes: the job records the failure itself.
        (new SendFcmMessage($this->push(), $driver->id))->handle($this->transport);

        // …and a configuration fault is emphatically NOT a dead handset.
        $this->assertSame('device-abc', $driver->fresh()->fcm_token);
    }

    /** The silent boot fault behind the original bug, now named out loud. */
    public function test_the_factory_reports_why_it_fell_back_to_the_log(): void
    {
        config([
            'services.fcm.project_id' => 'rvms-28129',
            'services.fcm.credentials' => 'storage/app/does-not-exist.json',
        ]);

        $factory = new FcmTransportFactory;

        $this->assertInstanceOf(LogFcmTransport::class, $factory->make());
        $this->assertStringContainsString('does-not-exist.json', $factory->fallbackReason());
    }

    public function test_a_fully_configured_app_reports_no_fallback_reason(): void
    {
        $path = storage_path('app/fake-credentials.json');
        file_put_contents($path, json_encode(['type' => 'service_account']));

        config([
            'services.fcm.project_id' => 'rvms-28129',
            'services.fcm.credentials' => 'storage/app/fake-credentials.json',
        ]);

        $factory = new FcmTransportFactory;

        try {
            $this->assertInstanceOf(FcmHttpV1Transport::class, $factory->make());
            $this->assertNull($factory->fallbackReason());
        } finally {
            @unlink($path);
        }
    }

    /** A Windows absolute path must not be treated as a relative one. */
    public function test_credential_paths_resolve_on_both_platforms(): void
    {
        $this->assertSame(
            base_path('storage/app/firebase.json'),
            FcmTransportFactory::resolvePath('storage/app/firebase.json')
        );
        $this->assertSame('C:\\keys\\firebase.json', FcmTransportFactory::resolvePath('C:\\keys\\firebase.json'));
        $this->assertSame('/etc/rvms/firebase.json', FcmTransportFactory::resolvePath('/etc/rvms/firebase.json'));
    }

    /** The doctor must refuse to pass while pushes are only simulated. */
    public function test_the_doctor_command_fails_when_pushes_are_simulated(): void
    {
        config(['services.fcm.project_id' => null, 'services.fcm.credentials' => null]);

        $this->artisan('rvms:fcm-doctor')
            ->expectsOutputToContain('SIMULATED')
            ->assertExitCode(1);
    }

    /**
     * A CA bundle configured at a path that holds nothing is cURL error 77,
     * not 60 — the state a php.ini edit with a typo leaves you in, and one
     * that reads as a totally different problem if it is not recognised.
     */
    public function test_a_missing_ca_bundle_is_recognised_as_a_certificate_fault(): void
    {
        Http::fake(fn () => throw new ConnectionException(
            'cURL error 77: error setting certificate file: C:\\xampp\\php\\cacert.pem'
        ));

        try {
            $this->transport->send($this->push());
            $this->fail('A certificate fault should have thrown.');
        } catch (FcmConfigurationException $e) {
            $this->assertStringContainsString('no file at the path it was given', $e->getMessage());
            $this->assertStringContainsString('rvms:fcm-doctor', $e->getMessage());
        }
    }

    /** No bundle at all is the other half, and gets the other instruction. */
    public function test_an_absent_ca_bundle_is_recognised_as_a_certificate_fault(): void
    {
        Http::fake(fn () => throw new ConnectionException(
            'cURL error 60: SSL certificate problem: unable to get local issuer certificate'
        ));

        try {
            $this->transport->send($this->push());
            $this->fail('A certificate fault should have thrown.');
        } catch (FcmConfigurationException $e) {
            $this->assertStringContainsString('no CA bundle configured', $e->getMessage());
        }
    }

    /**
     * The bug that made R7 look intermittent: PM reminders arrived, vehicle
     * status updates never did, and both reported a queued job. The status
     * payload carried `from`, which FCM reserves, so Google rejected that one
     * message type and only that one.
     */
    public function test_a_reserved_data_key_is_refused_before_google_ever_sees_it(): void
    {
        $this->expectException(FcmConfigurationException::class);
        $this->expectExceptionMessageMatches('/reserves it|reserves for its own protocol/');

        (new FcmMessage('device-abc', 'Vehicle Status Updated', 'ABC-1234 is now Dispatched.', [
            'from' => 'Operational',
        ]))->toPayload();
    }

    /** Anything namespaced to Google is reserved too, in any casing. */
    public function test_google_and_gcm_prefixed_keys_are_refused(): void
    {
        foreach (['google_id', 'GCM_thing', 'MESSAGE_TYPE'] as $key) {
            try {
                (new FcmMessage('device-abc', 'T', 'B', [$key => 'x']))->toPayload();
                $this->fail("\"{$key}\" should have been refused.");
            } catch (FcmConfigurationException) {
                $this->addToAssertionCount(1);
            }
        }
    }

    /**
     * The real status-change payload, end to end. A rename that satisfied the
     * guard but broke the alert would pass every test above.
     */
    public function test_the_vehicle_status_alert_builds_a_payload_fcm_accepts(): void
    {
        $agency = Agency::factory()->create(['code' => 'BFP']);
        $driver = User::factory()->driver()->create([
            'agency_id' => $agency->id,
            'fcm_token' => 'device-abc',
        ]);
        $vehicle = Vehicle::factory()->create([
            'agency_id' => $agency->id,
            'assigned_driver_id' => $driver->id,
            'status' => Vehicle::STATUS_OPERATIONAL,
        ]);

        Bus::fake();

        app(VehicleStatusWriter::class)->write(
            $vehicle,
            Vehicle::STATUS_NOT_OPERATIONAL,
            VehicleStatusWriter::SOURCE_VEHICLES,
        );

        Bus::assertDispatched(SendFcmMessage::class, function (SendFcmMessage $job) {
            // toPayload() throws on a reserved key, so building it IS the check.
            $payload = (new ReflectionProperty($job, 'message'))->getValue($job)->toPayload();

            $this->assertSame('Operational', $payload['message']['data']['from_status']);
            $this->assertSame('Not Operational', $payload['message']['data']['to_status']);

            return true;
        });
    }

    /** Device tokens are credentials — the log gets a stub, never the token. */
    public function test_a_logged_token_is_masked(): void
    {
        $masked = FcmHttpV1Transport::maskedToken(str_repeat('a', 160));

        $this->assertStringNotContainsString(str_repeat('a', 20), $masked);
        $this->assertStringContainsString('160 chars', $masked);
    }
}
