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

    /**
     * THE ONE THAT COST AN EVENING (2026-08-28).
     *
     * Railway's raw variable editor rewrites the two characters \ and n inside
     * a JSON string into a REAL line break when the value is saved — verified
     * on the deployed service, where the stored value was the full 2,296 bytes,
     * ended correctly, and still failed with "Control character error,
     * possibly incorrectly encoded".
     *
     * A raw newline cannot legally sit inside a JSON string, so json_decode
     * refuses the whole document and the deployment reports a key that is
     * present, complete and unusable. Nothing about the key is wrong — only
     * its transport — so it is repaired rather than rejected.
     */
    public function test_a_key_whose_newlines_an_editor_rewrote_is_repaired(): void
    {
        $good = $this->fakeKey();

        // Exactly what the editor did: \n inside the private key became a real
        // line break. Whitespace BETWEEN fields would have been harmless.
        $mangled = str_replace('\\n', "\n", $good);

        $this->assertNull(json_decode($mangled, true),
            'The premise of this test is that the mangled value does not parse.');
        $this->assertSame(JSON_ERROR_CTRL_CHAR, json_last_error(),
            'It must fail specifically as a control-character error — that is what is repaired.');

        $path = FcmTransportFactory::resolveCredentialsPath($mangled);

        $this->assertNotNull($path, 'A key broken only by its transport must still be usable.');

        $written = json_decode((string) file_get_contents($path), true);

        $this->assertIsArray($written, 'The repaired key must be written back as valid JSON.');
        $this->assertSame(
            json_decode($good, true)['private_key'],
            $written['private_key'],
            'The private key must come back byte-identical — a repair that alters the key is worse '
            .'than a refusal, because it fails later and somewhere else.'
        );
    }

    /**
     * base64 is the recommended transport precisely because nothing can damage
     * it: letters, digits, '+', '/' and '=' only. No quotes for an editor to
     * escape, no newlines for it to rewrite.
     */
    public function test_the_key_may_be_supplied_as_base64(): void
    {
        $path = FcmTransportFactory::resolveCredentialsPath(base64_encode($this->fakeKey()));

        $this->assertNotNull($path);
        $this->assertSame(
            json_decode($this->fakeKey(), true),
            json_decode((string) file_get_contents($path), true)
        );
    }

    /** Some tools wrap base64 at 64 columns; the value is still the same key. */
    public function test_base64_survives_being_wrapped_across_lines(): void
    {
        $this->assertNotNull(
            FcmTransportFactory::resolveCredentialsPath(chunk_split(base64_encode($this->fakeKey()), 64))
        );
    }

    /**
     * The base64 check runs before the path check, so it must not swallow a
     * filesystem path — which is still how a laptop supplies the key.
     */
    public function test_a_filesystem_path_is_not_mistaken_for_base64(): void
    {
        $this->assertSame(
            '/etc/rvms/firebase.json',
            FcmTransportFactory::resolveCredentialsPath('/etc/rvms/firebase.json')
        );
    }

    /** Repairing transport damage must not become accepting nonsense. */
    public function test_a_value_that_is_not_a_key_is_still_refused(): void
    {
        $this->assertNull(FcmTransportFactory::resolveCredentialsPath('{not json'));
        $this->assertNull(FcmTransportFactory::resolveCredentialsPath(
            base64_encode('{"type":"service_account"}')
        ), 'Decoding is not enough — a key still needs client_email and private_key.');
    }
}
