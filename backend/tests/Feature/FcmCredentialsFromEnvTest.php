<?php

namespace Tests\Feature;

use App\Services\Fcm\FcmTransportFactory;
use Tests\TestCase;

/**
 * The Firebase key has to arrive as an environment variable, not only as a file.
 *
 * On a laptop, FIREBASE_CREDENTIALS points at a JSON file sitting in storage/.
 * A hosting platform has no such filesystem: there is nowhere to upload a
 * private key, and anything written is wiped on the next deploy. Secrets there
 * arrive as environment variables, so the value has to be allowed to be the
 * JSON itself.
 *
 * Without this the only ways to deploy were to commit a private key to the
 * repository or to ship with FR-21 pushes silently falling back to the log —
 * silently, because the fallback is by design and looks identical to a machine
 * that was never configured for Firebase at all.
 */
class FcmCredentialsFromEnvTest extends TestCase
{
    /** A syntactically valid service-account key. The private key is a throwaway. */
    private function fakeKey(array $overrides = []): string
    {
        return json_encode(array_merge([
            'type' => 'service_account',
            'project_id' => 'rvms-test',
            'private_key_id' => 'abc123',
            'private_key' => "-----BEGIN PRIVATE KEY-----\nnot-a-real-key\n-----END PRIVATE KEY-----\n",
            'client_email' => 'rvms@rvms-test.iam.gserviceaccount.com',
            'client_id' => '1234567890',
        ], $overrides));
    }

    protected function tearDown(): void
    {
        @unlink(storage_path('app/firebase-credentials.json'));

        parent::tearDown();
    }

    public function test_json_in_the_environment_is_written_to_a_readable_file(): void
    {
        $path = FcmTransportFactory::resolveCredentialsPath($this->fakeKey());

        $this->assertNotNull($path);
        $this->assertTrue(is_readable($path), 'The key should have been written somewhere readable.');
        $this->assertSame($this->fakeKey(), file_get_contents($path));
    }

    public function test_the_written_key_is_not_reachable_from_the_web_root(): void
    {
        $path = FcmTransportFactory::resolveCredentialsPath($this->fakeKey());

        // It is a private key: it must not land anywhere a browser could fetch it.
        $this->assertStringStartsWith(storage_path(), $path);
        $this->assertStringNotContainsString(public_path(), $path);
    }

    public function test_a_plain_path_is_still_treated_as_a_path(): void
    {
        // The laptop and shared-hosting case must not change.
        $this->assertSame(
            base_path('storage/app/firebase.json'),
            FcmTransportFactory::resolveCredentialsPath('storage/app/firebase.json')
        );

        $this->assertSame(
            '/etc/rvms/firebase.json',
            FcmTransportFactory::resolveCredentialsPath('/etc/rvms/firebase.json')
        );
    }

    public function test_json_missing_the_private_key_is_refused_rather_than_written(): void
    {
        $this->assertNull(FcmTransportFactory::resolveCredentialsPath($this->fakeKey(['private_key' => ''])));
        $this->assertNull(FcmTransportFactory::resolveCredentialsPath($this->fakeKey(['client_email' => ''])));
        $this->assertNull(FcmTransportFactory::resolveCredentialsPath('{"not":"a key"}'));
    }

    public function test_malformed_json_is_refused_rather_than_written(): void
    {
        $this->assertNull(FcmTransportFactory::resolveCredentialsPath('{"type": "service_account"'));
    }

    public function test_rewriting_only_happens_when_the_key_changes(): void
    {
        $path = FcmTransportFactory::resolveCredentialsPath($this->fakeKey());
        $first = filemtime($path);

        // Same value again: the file must be left alone, since this runs on
        // every boot and the key is read far more often than it is rotated.
        clearstatcache();
        FcmTransportFactory::resolveCredentialsPath($this->fakeKey());
        $this->assertSame($first, filemtime($path));

        // A different value must replace it.
        FcmTransportFactory::resolveCredentialsPath($this->fakeKey(['project_id' => 'rvms-other']));
        $this->assertStringContainsString('rvms-other', (string) file_get_contents($path));
    }

    public function test_the_transport_falls_back_with_a_named_reason_on_a_bad_key(): void
    {
        config([
            'services.fcm.project_id' => 'rvms-test',
            'services.fcm.credentials' => '{"type":"service_account"}',   // no private_key
        ]);

        $factory = new FcmTransportFactory;
        $factory->make();

        $this->assertStringContainsString('client_email', (string) $factory->fallbackReason());
    }
}
