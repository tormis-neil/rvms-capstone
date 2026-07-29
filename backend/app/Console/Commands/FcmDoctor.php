<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Services\Fcm\FcmHttpV1Transport;
use App\Services\Fcm\FcmMessage;
use App\Services\Fcm\FcmTransport;
use App\Services\Fcm\FcmTransportFactory;
use App\Services\Fcm\LogFcmTransport;
use Google\Auth\Credentials\ServiceAccountCredentials;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Throwable;

/**
 * Checks the whole push path, one stage at a time, and says what to fix (FR-21).
 *
 * A queue worker reports a failed push as "FAIL" and a duration — the same
 * three characters whether the vendor folder is stale, the service-account key
 * is for another project, the machine cannot verify Google's certificate, or
 * the handset simply uninstalled the app. Those have nothing in common except
 * the symptom, so this walks the stages in order and stops at the first one
 * that breaks, with the fix for that stage.
 */
class FcmDoctor extends Command
{
    protected $signature = 'rvms:fcm-doctor
                            {--user= : Send a real test push to this user (id or email)}
                            {--token= : Send a real test push to this raw device token}';

    protected $description = 'Diagnose Firebase Cloud Messaging setup and optionally send a test push (FR-21)';

    public function handle(): int
    {
        $this->components->info('RVMS — FCM diagnostics');

        return $this->checkConfiguration()
            && $this->checkLibrary()
            && $this->checkCacheStore()
            && $this->checkCredentialsFile()
            && $this->checkAccessToken()
            && $this->checkRegisteredDevices()
            && $this->maybeSendTestPush()
                ? self::SUCCESS
                : self::FAILURE;
    }

    /** Stage 1 — is the app even trying to send real pushes? */
    private function checkConfiguration(): bool
    {
        $projectId = config('services.fcm.project_id');
        $credentials = config('services.fcm.credentials');
        $caBundle = FcmTransportFactory::caBundle();

        $this->line('  <fg=gray>FIREBASE_PROJECT_ID  :</> '.($projectId ?: '<fg=yellow>(blank)</>'));
        $this->line('  <fg=gray>FIREBASE_CREDENTIALS :</> '.($credentials ?: '<fg=yellow>(blank)</>'));
        $this->line('  <fg=gray>FIREBASE_CA_BUNDLE   :</> '.($caBundle ?: '(not set — using PHP\'s own certificate store)'));
        $this->line('  <fg=gray>QUEUE_CONNECTION     :</> '.config('queue.default'));
        $this->newLine();

        $factory = app(FcmTransportFactory::class);
        $transport = $factory->make();

        if ($transport instanceof LogFcmTransport) {
            $this->components->error('Pushes are being SIMULATED — nothing reaches any phone.');
            $this->components->bulletList([$factory->fallbackReason() ?? 'Unknown reason.']);
            $this->hint('Set both values in backend/.env, then run `php artisan config:clear`.');

            return false;
        }

        $this->components->twoColumnDetail('Transport in use', '<fg=green>FcmHttpV1Transport (real pushes)</>');

        // A stale compiled config is a classic false alarm: .env was edited but
        // config:cache still holds the old values, so nothing appears to change.
        if (file_exists(base_path('bootstrap/cache/config.php'))) {
            $this->hint('bootstrap/cache/config.php exists — .env edits are ignored until you run `php artisan config:clear`.');
        }

        return true;
    }

    /** Stage 2 — the library that reads the key. */
    private function checkLibrary(): bool
    {
        if (! class_exists(ServiceAccountCredentials::class)) {
            $this->components->error('The google/auth package is not installed.');
            $this->hint('Run `composer install` inside backend/, then restart the queue worker.');

            return false;
        }

        $this->components->twoColumnDetail('google/auth', '<fg=green>installed</>');

        foreach (['curl', 'openssl', 'json'] as $extension) {
            if (! extension_loaded($extension)) {
                $this->components->error("The PHP extension \"{$extension}\" is not loaded — HTTPS calls to Google cannot work.");

                return false;
            }
        }

        return true;
    }

    /**
     * Stage 3 — the cache, which the transport depends on and which is easy to
     * overlook: the OAuth token is cached, so with CACHE_STORE=database and no
     * `cache` table every push throws before it ever reaches Google.
     */
    private function checkCacheStore(): bool
    {
        try {
            Cache::put('rvms.fcm-doctor', 'ok', 10);
            $readBack = Cache::get('rvms.fcm-doctor');
            Cache::forget('rvms.fcm-doctor');
        } catch (Throwable $e) {
            $this->components->error('The cache store ("'.config('cache.default').'") is not usable, and the FCM access token is cached.');
            $this->line('  '.$e->getMessage());
            $this->hint('With CACHE_STORE=database this usually means the `cache` table is missing — run `php artisan migrate`.');

            return false;
        }

        if ($readBack !== 'ok') {
            $this->components->error('The cache store ("'.config('cache.default').'") accepted a write but did not return it.');

            return false;
        }

        $this->components->twoColumnDetail('Cache store ('.config('cache.default').')', '<fg=green>usable</>');

        return true;
    }

    /** Stage 4 — the service-account key itself. */
    private function checkCredentialsFile(): bool
    {
        $path = FcmTransportFactory::resolvePath(config('services.fcm.credentials'));

        if (! is_readable($path)) {
            $this->components->error("The service-account key at {$path} cannot be read.");

            return false;
        }

        $key = json_decode((string) file_get_contents($path), true);

        if (! is_array($key)) {
            $this->components->error("{$path} is not valid JSON — it may have been saved with the wrong extension or truncated.");

            return false;
        }

        foreach (['type', 'project_id', 'client_email', 'private_key'] as $field) {
            if (blank($key[$field] ?? null)) {
                $this->components->error("The key at {$path} has no \"{$field}\" — this is not a service-account key.");
                $this->hint('Download it from Firebase console → Project settings → Service accounts → Generate new private key.');

                return false;
            }
        }

        if (($key['type'] ?? null) !== 'service_account') {
            $this->components->error('The key\'s "type" is "'.$key['type'].'", not "service_account".');

            return false;
        }

        if (openssl_pkey_get_private($key['private_key']) === false) {
            $this->components->error('The private key inside the JSON will not parse.');
            $this->hint('The file was probably edited by hand — the \n escapes in private_key must stay exactly as downloaded.');

            return false;
        }

        $this->components->twoColumnDetail('Service-account key', '<fg=green>valid</>');
        $this->components->twoColumnDetail('  client_email', $key['client_email']);
        $this->components->twoColumnDetail('  project_id in key', $key['project_id']);

        // The quiet killer: a valid key for a DIFFERENT project. Every call is
        // authenticated and every call is refused.
        if ($key['project_id'] !== config('services.fcm.project_id')) {
            $this->components->error(
                'The key belongs to project "'.$key['project_id'].'" but FIREBASE_PROJECT_ID is "'
                .config('services.fcm.project_id').'". Every send will be denied.'
            );
            $this->hint('Set FIREBASE_PROJECT_ID='.$key['project_id'].' in .env, or download the key for the right project.');

            return false;
        }

        return true;
    }

    /** Stage 5 — can we actually mint a token from Google? (first network call) */
    private function checkAccessToken(): bool
    {
        // Always test for real rather than reading back a token cached up to 50
        // minutes ago, which would hide the very failure being diagnosed.
        Cache::forget(FcmHttpV1Transport::CACHE_KEY);

        $started = microtime(true);

        try {
            app(FcmTransport::class)->send(new FcmMessage(
                token: 'rvms-doctor-connectivity-probe',
                title: 'RVMS connectivity probe',
                body: 'This token is deliberately invalid — only the connection is being tested.',
                data: ['type' => 'Doctor'],
            ));
        } catch (Throwable $e) {
            $this->newLine();
            $this->components->error('Could not complete a call to Google.');
            $this->line('  '.wordwrap($e->getMessage(), 100, PHP_EOL.'  '));

            if (str_contains($e->getMessage(), "verify Google's TLS certificate")) {
                $this->reportCertificateEnvironment();
            }

            return false;
        }

        $ms = (int) round((microtime(true) - $started) * 1000);

        // A deliberately bogus token coming back as a plain rejection is the
        // proof we want: authentication worked and FCM answered us.
        $this->components->twoColumnDetail(
            'Reached Google and authenticated',
            "<fg=green>yes ({$ms} ms)</>"
        );

        return true;
    }

    /**
     * What certificate store this PHP is ACTUALLY using — printed only when the
     * TLS handshake failed.
     *
     * Editing php.ini and seeing no change is the normal next step here, and it
     * has three usual explanations, none of which are visible from the error:
     * the setting was edited in a php.ini this PHP does not read (Apache's,
     * or a second PHP install earlier on the PATH), the line is still
     * commented out, or the path it points at does not exist. Rather than
     * guess, show the values PHP reports for itself.
     */
    private function reportCertificateEnvironment(): void
    {
        $this->newLine();
        $this->components->info('The certificate settings THIS php is using');

        $this->components->twoColumnDetail('php binary', PHP_BINARY);
        $this->components->twoColumnDetail(
            'php.ini it loaded',
            php_ini_loaded_file() ?: '<fg=red>(none — PHP is running with no php.ini at all)</>'
        );

        // Any of these can also set curl.cainfo, so their existence matters —
        // but the full list is pages long on Linux and empty on XAMPP, so the
        // directory and the count are what is worth reading.
        if ($extra = php_ini_scanned_files()) {
            $files = array_filter(array_map('trim', explode(',', $extra)));
            $this->components->twoColumnDetail(
                'extra .ini files',
                count($files).' in '.dirname((string) reset($files))
            );
        }

        foreach (['curl.cainfo', 'openssl.cafile', 'openssl.capath'] as $setting) {
            $this->components->twoColumnDetail($setting, self::describeCaPath((string) ini_get($setting)));
        }

        $locations = openssl_get_cert_locations();
        $this->components->twoColumnDetail(
            'OpenSSL built-in default',
            self::describeCaPath((string) ($locations['default_cert_file'] ?? ''))
        );

        $this->newLine();
        $this->hint('The php.ini named above is the ONLY one `php artisan` and `php artisan queue:work` read. '
            .'Apache has its own — editing that one changes nothing for the queue worker.');
        $this->hint('If a setting shows "(not set)" after you edited it: the line is probably still commented '
            .'out with a leading ";", or a later duplicate of the same key further down the file is winning.');
        $this->hint('If a path shows "(MISSING)": download https://curl.se/ca/cacert.pem to exactly that path.');
        $this->hint('To skip php.ini entirely: download https://curl.se/ca/cacert.pem into '
            .'backend/storage/app/, put FIREBASE_CA_BUNDLE=storage/app/cacert.pem in backend/.env, and '
            .'run `php artisan config:clear`. Keep the path relative — an absolute Windows path breaks '
            .'dotenv on its spaces unquoted, and on its backslashes double-quoted.');
    }

    private static function describeCaPath(string $path): string
    {
        if (blank($path)) {
            return '<fg=red>(not set)</>';
        }

        return is_readable($path)
            ? $path.' <fg=green>(exists)</>'
            : $path.' <fg=red>(MISSING — nothing at this path)</>';
    }

    /** Stage 6 — is there a handset to send to at all? */
    private function checkRegisteredDevices(): bool
    {
        try {
            $devices = User::query()
                ->whereNotNull('fcm_token')
                ->get(['id', 'name', 'email', 'role', 'fcm_token']);
        } catch (Throwable $e) {
            $this->components->error('Could not read the users table: '.$e->getMessage());
            $this->hint('Is MySQL running, and has `php artisan migrate` been run?');

            return false;
        }

        $this->newLine();

        if ($devices->isEmpty()) {
            $this->components->warn('No user has a registered device token, so no push has anywhere to go.');
            $this->hint('Sign in on the phone app — it registers the device via POST /api/v1/fcm-token. Then run this again.');

            return true; // Everything server-side is sound; this is a device step.
        }

        $this->components->twoColumnDetail('Registered devices', (string) $devices->count());

        foreach ($devices as $device) {
            $this->components->twoColumnDetail(
                "  {$device->email} ({$device->role})",
                FcmHttpV1Transport::maskedToken($device->fcm_token)
            );
        }

        return true;
    }

    /** Stage 7 — optional: a real banner on a real phone. */
    private function maybeSendTestPush(): bool
    {
        $token = $this->option('token');
        $user = null;

        if ($this->option('user')) {
            $user = User::query()
                ->where('id', $this->option('user'))
                ->orWhere('email', $this->option('user'))
                ->first();

            if (! $user) {
                $this->components->error('No user matches --user='.$this->option('user'));

                return false;
            }

            if (blank($user->fcm_token)) {
                $this->components->error($user->email.' has no registered device — sign in on the phone app first.');

                return false;
            }

            $token = $user->fcm_token;
        }

        if (blank($token)) {
            $this->newLine();
            $this->components->info('Server side is healthy. To put a real banner on a phone, run:');
            $this->line('  php artisan rvms:fcm-doctor --user=driver@example.com');

            return true;
        }

        $this->newLine();
        $this->components->info('Sending a test push…');

        try {
            $delivered = app(FcmTransport::class)->send(new FcmMessage(
                token: $token,
                title: 'RVMS test notification',
                body: 'If you can read this, push delivery is working.',
                data: ['type' => 'Doctor'],
            ));
        } catch (Throwable $e) {
            $this->components->error('The send failed.');
            $this->line('  '.wordwrap($e->getMessage(), 100, PHP_EOL.'  '));

            return false;
        }

        if (! $delivered) {
            $this->components->error('FCM refused that device token — the handset is gone or the token is stale.');
            $this->hint('Reinstall/sign in again on the phone so it registers a fresh token, then retry.');

            return false;
        }

        $this->components->info('FCM accepted the push.');
        $this->hint('No banner on the phone? Then it is device-side: Android 13+ notification permission, '
            .'the app installed from a different Firebase project, or battery optimisation. '
            .'Background the app before testing — a foreground app handles the message itself.');

        return true;
    }

    private function hint(string $text): void
    {
        $this->line('  <fg=cyan>→</> '.wordwrap($text, 100, PHP_EOL.'    '));
    }
}
