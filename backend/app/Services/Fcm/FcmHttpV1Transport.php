<?php

namespace App\Services\Fcm;

use Google\Auth\Credentials\ServiceAccountCredentials;
use Google\Auth\HttpHandler\HttpHandlerFactory;
use GuzzleHttp\Client as GuzzleClient;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;
use Throwable;

/**
 * The real sender: Google's FCM HTTP v1 API, server-side in PHP (per the
 * approved conventions — no client-side sending, and never the deprecated
 * legacy API).
 *
 * Authentication is an OAuth2 access token minted from the service-account
 * key. Those tokens last about an hour, so minting one per push would add a
 * round trip to Google to every notification; it is cached just short of its
 * real expiry instead.
 *
 * Every failure path below names WHICH of three things went wrong — minting
 * the token, reaching Google, or the message itself — because from a queue
 * worker they look identical ("FAIL" and a duration) and are fixed in three
 * completely different places.
 */
class FcmHttpV1Transport implements FcmTransport
{
    private const SCOPE = 'https://www.googleapis.com/auth/firebase.messaging';

    public const CACHE_KEY = 'fcm.access_token';

    public function __construct(
        private readonly string $projectId,
        private readonly string $credentialsPath,
        private readonly ?string $caBundle = null,
    ) {}

    public function send(FcmMessage $message): bool
    {
        $response = $this->post($message);

        if ($response->successful()) {
            return true;
        }

        return $this->handleFailure($response, $message);
    }

    /** The POST itself, with connection errors named rather than left raw. */
    private function post(FcmMessage $message): Response
    {
        $request = Http::withToken($this->accessToken())
            ->acceptJson()
            ->timeout(15)
            ->connectTimeout(10);

        if ($this->caBundle !== null) {
            $request = $request->withOptions(['verify' => $this->caBundle]);
        }

        try {
            return $request->post(
                "https://fcm.googleapis.com/v1/projects/{$this->projectId}/messages:send",
                $message->toPayload(),
            );
        } catch (ConnectionException $e) {
            throw $this->describeConnectionFailure($e, 'sending the push to fcm.googleapis.com');
        }
    }

    /**
     * A non-2xx answer from FCM. Three outcomes, and choosing the wrong one is
     * destructive: treating a configuration fault as a dead token would wipe
     * the driver's registration, making a switched-off API look like a phone
     * that never registered.
     */
    private function handleFailure(Response $response, FcmMessage $message): bool
    {
        $status = $response->status();
        $error = (string) $response->json('error.status');
        $body = $response->body();

        // The device is genuinely gone, or its token belongs to a different
        // Firebase project. Non-delivery, not an outage — the caller clears
        // the token so the system stops trying.
        if ($status === 404 || in_array($error, ['UNREGISTERED', 'NOT_FOUND', 'SENDER_ID_MISMATCH'], true)) {
            Log::warning('FCM rejected a device token', [
                'status' => $status,
                'error' => $error,
                'token' => self::maskedToken($message->token),
            ]);

            return false;
        }

        // 400 is ambiguous: a malformed registration token (dead device) or a
        // malformed message (our bug). Only the first justifies clearing the
        // token, so the body is inspected rather than assumed.
        if ($status === 400) {
            if (str_contains($body, 'registration token') || str_contains($body, 'message.token')) {
                Log::warning('FCM rejected a device token as malformed', [
                    'token' => self::maskedToken($message->token),
                    'body' => $body,
                ]);

                return false;
            }

            throw new FcmConfigurationException(
                'FCM rejected the message payload (400 INVALID_ARGUMENT). This is a fault in the '
                .'message we built, not a bad device token. Google said: '.$body
            );
        }

        // The access token was refused. It can legitimately go stale early (a
        // rotated key, a clock correction), so the cached copy is dropped and
        // the retry mints a fresh one — retrying with the SAME rejected token,
        // as before, could only fail three times.
        if ($status === 401) {
            Cache::forget(self::CACHE_KEY);

            throw new RuntimeException(
                'FCM refused the access token (401 UNAUTHENTICATED). The cached token has been '
                .'discarded; the retry will mint a new one. Google said: '.$body
            );
        }

        // Not the token and not the device: the project or the service account.
        if ($status === 403) {
            throw new FcmConfigurationException(
                'FCM denied the request (403 '.$error.'). Usually one of: the "Firebase Cloud '
                .'Messaging API" is not enabled for project '.$this->projectId.' in the Google '
                .'Cloud console, the service-account key belongs to a different project, or that '
                .'account lacks the Firebase Messaging role. Google said: '.$body
            );
        }

        // 429/5xx are genuinely transient — let the queue's backoff retry.
        throw new RuntimeException("FCM send failed with status {$status}: ".$body);
    }

    /** A short-lived OAuth2 token, cached just inside its own expiry. */
    private function accessToken(): string
    {
        return Cache::remember(self::CACHE_KEY, now()->addMinutes(50), function (): string {
            try {
                $credentials = new ServiceAccountCredentials(self::SCOPE, $this->credentialsPath);
                $token = $credentials->fetchAuthToken($this->authHandler());
            } catch (Throwable $e) {
                throw $this->describeMintFailure($e);
            }

            if (empty($token['access_token'])) {
                throw new FcmConfigurationException(
                    'Google returned no access token for the key at '.$this->credentialsPath
                    .'. The file is readable but is probably not a service-account key.'
                );
            }

            return $token['access_token'];
        });
    }

    /**
     * google/auth builds its own Guzzle client, which would ignore the CA
     * bundle configured for the rest of the app — so on a machine that needs
     * one, minting the token would fail before the push was ever attempted.
     */
    private function authHandler(): ?callable
    {
        if ($this->caBundle === null) {
            return null;
        }

        return HttpHandlerFactory::build(new GuzzleClient(['verify' => $this->caBundle]));
    }

    /** Turn a token-minting failure into something actionable. */
    private function describeMintFailure(Throwable $e): Throwable
    {
        $message = $e->getMessage();

        if (self::looksLikeCertificateFailure($message)) {
            return $this->certificateException($message, 'minting the FCM access token');
        }

        if (str_contains($message, 'invalid_grant')) {
            return new FcmConfigurationException(
                'Google rejected the service-account key (invalid_grant). This is normally a '
                .'server clock more than a few minutes out of step with real time, or a key that '
                ."has been revoked in the Firebase console. Google said: {$message}"
            );
        }

        return new FcmConfigurationException(
            "Could not mint an FCM access token from {$this->credentialsPath} — {$message}",
            0,
            $e
        );
    }

    /** Turn a cURL-level failure into something actionable. */
    private function describeConnectionFailure(ConnectionException $e, string $during): Throwable
    {
        $message = $e->getMessage();

        if (self::looksLikeCertificateFailure($message)) {
            return $this->certificateException($message, $during);
        }

        return new RuntimeException("Could not reach Google while {$during} — {$message}", 0, $e);
    }

    private function certificateException(string $message, string $during): FcmConfigurationException
    {
        return new FcmConfigurationException(
            "This machine cannot verify Google's TLS certificate while {$during}. PHP has no CA "
            .'bundle configured. Fix it once in php.ini — download https://curl.se/ca/cacert.pem '
            .'and set curl.cainfo and openssl.cafile to its full path, then restart Apache and the '
            .'queue worker — or point FIREBASE_CA_BUNDLE at that file in .env. '
            ."The underlying error was: {$message}"
        );
    }

    private static function looksLikeCertificateFailure(string $message): bool
    {
        return str_contains($message, 'SSL certificate problem')
            || str_contains($message, 'cURL error 60')
            || str_contains($message, 'unable to get local issuer certificate')
            || str_contains($message, 'certificate verify failed');
    }

    /** Device tokens are credentials — never write a whole one to the log. */
    public static function maskedToken(string $token): string
    {
        return substr($token, 0, 12).'…('.strlen($token).' chars)';
    }
}
