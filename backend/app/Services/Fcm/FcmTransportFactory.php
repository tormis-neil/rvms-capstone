<?php

namespace App\Services\Fcm;

use Google\Auth\Credentials\ServiceAccountCredentials;
use Illuminate\Support\Facades\Log;

/**
 * Decides which sender the app uses, and remembers WHY (FR-21).
 *
 * Previously this lived inline in AppServiceProvider and simply returned the
 * log transport whenever anything was missing. That silence was the problem:
 * with `google/auth` absent from vendor/, the app booted happily, every
 * notification row was written, and the only symptom was a queue worker
 * failing in a few milliseconds with a class-not-found buried in the log. The
 * decision is now explicit, the reason is recorded for `rvms:fcm-doctor` to
 * read back, and falling back while Firebase IS configured writes a named
 * warning rather than nothing at all.
 */
class FcmTransportFactory
{
    /** Why the log transport was chosen; null when the real sender is live. */
    private ?string $fallbackReason = null;

    public function make(): FcmTransport
    {
        $this->fallbackReason = null;

        $projectId = config('services.fcm.project_id');
        $credentials = config('services.fcm.credentials');

        // Nothing configured at all is the normal local-development state:
        // build and test every trigger without Firebase, pushes go to the log.
        if (blank($projectId) && blank($credentials)) {
            $this->fallbackReason = 'FIREBASE_PROJECT_ID and FIREBASE_CREDENTIALS are both blank — pushes are simulated in the log.';

            return new LogFcmTransport;
        }

        if (blank($projectId)) {
            return $this->fallBack('FIREBASE_CREDENTIALS is set but FIREBASE_PROJECT_ID is blank.');
        }

        if (blank($credentials)) {
            return $this->fallBack('FIREBASE_PROJECT_ID is set but FIREBASE_CREDENTIALS is blank.');
        }

        $path = self::resolvePath($credentials);

        if (! is_readable($path)) {
            return $this->fallBack("The service-account key at {$path} does not exist or cannot be read.");
        }

        // The failure that cost a day of manual testing: the binding checked
        // the key and the project id but never that the library which reads
        // them was installed, so the app chose the real transport and then
        // threw inside the queue worker on every single push.
        if (! class_exists(ServiceAccountCredentials::class)) {
            return $this->fallBack('The google/auth package is not installed — run `composer install` in backend/.');
        }

        return new FcmHttpV1Transport($projectId, $path, self::caBundle());
    }

    /** Why the log transport is in use, or null when pushes really are sent. */
    public function fallbackReason(): ?string
    {
        return $this->fallbackReason;
    }

    /** An absolute path to the service-account key, however it was written. */
    public static function resolvePath(string $credentials): string
    {
        $isAbsolute = str_starts_with($credentials, DIRECTORY_SEPARATOR)
            || (bool) preg_match('/^[A-Za-z]:[\\\\\/]/', $credentials); // C:\... on Windows

        return $isAbsolute ? $credentials : base_path($credentials);
    }

    /**
     * An optional CA bundle for machines whose PHP has no certificate store —
     * the usual XAMPP-on-Windows case, where every HTTPS call to Google dies
     * with "cURL error 60: SSL certificate problem". Left null, PHP's own
     * configured store is used, which is correct on a properly set-up server.
     */
    public static function caBundle(): ?string
    {
        $bundle = config('services.fcm.ca_bundle');

        return blank($bundle) ? null : self::resolvePath($bundle);
    }

    private function fallBack(string $reason): LogFcmTransport
    {
        $this->fallbackReason = $reason;

        // Loud, because reaching here means someone MEANT to send real pushes.
        Log::warning('FCM: falling back to the log transport — no push will reach any device. '.$reason);

        return new LogFcmTransport;
    }
}
