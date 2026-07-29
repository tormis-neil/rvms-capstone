<?php

namespace Tests\Feature;

use App\Jobs\SendFcmMessage;
use App\Models\Agency;
use App\Models\User;
use App\Services\Fcm\FcmConfigurationException;
use App\Services\Fcm\FcmHttpV1Transport;
use App\Services\Fcm\FcmMessage;
use App\Services\Fcm\FcmTransport;
use App\Services\Fcm\FcmTransportFactory;
use App\Services\Fcm\LogFcmTransport;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
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

    /** Device tokens are credentials — the log gets a stub, never the token. */
    public function test_a_logged_token_is_masked(): void
    {
        $masked = FcmHttpV1Transport::maskedToken(str_repeat('a', 160));

        $this->assertStringNotContainsString(str_repeat('a', 20), $masked);
        $this->assertStringContainsString('160 chars', $masked);
    }
}
