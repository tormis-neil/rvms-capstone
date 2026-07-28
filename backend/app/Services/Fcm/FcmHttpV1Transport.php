<?php

namespace App\Services\Fcm;

use Google\Auth\Credentials\ServiceAccountCredentials;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;

/**
 * The real sender: Google's FCM HTTP v1 API, server-side in PHP (per the
 * approved conventions — no client-side sending, and never the deprecated
 * legacy API).
 *
 * Authentication is an OAuth2 access token minted from the service-account
 * key. Those tokens last about an hour, so minting one per push would add a
 * round trip to Google to every notification; it is cached just short of its
 * real expiry instead.
 */
class FcmHttpV1Transport implements FcmTransport
{
    private const SCOPE = 'https://www.googleapis.com/auth/firebase.messaging';

    private const CACHE_KEY = 'fcm.access_token';

    public function __construct(
        private readonly string $projectId,
        private readonly string $credentialsPath,
    ) {}

    public function send(FcmMessage $message): bool
    {
        $response = Http::withToken($this->accessToken())
            ->acceptJson()
            ->post(
                "https://fcm.googleapis.com/v1/projects/{$this->projectId}/messages:send",
                $message->toPayload(),
            );

        if ($response->successful()) {
            return true;
        }

        // UNREGISTERED / INVALID_ARGUMENT mean the handset is gone or the token
        // is stale — a normal fact of life, not an outage. Report it as a
        // non-delivery so the caller can clear the token, and do not throw:
        // retrying a dead token forever would just fill the failed-jobs table.
        if (in_array($response->status(), [400, 403, 404], true)) {
            Log::warning('FCM rejected a device token', [
                'status' => $response->status(),
                'error' => $response->json('error.status'),
            ]);

            return false;
        }

        // 429/5xx are genuinely transient — let the queue's backoff retry.
        throw new RuntimeException(
            "FCM send failed with status {$response->status()}: ".$response->body()
        );
    }

    /** A short-lived OAuth2 token, cached just inside its own expiry. */
    private function accessToken(): string
    {
        return Cache::remember(self::CACHE_KEY, now()->addMinutes(50), function (): string {
            $credentials = new ServiceAccountCredentials(self::SCOPE, $this->credentialsPath);
            $token = $credentials->fetchAuthToken();

            if (empty($token['access_token'])) {
                throw new RuntimeException(
                    'Could not mint an FCM access token from '.$this->credentialsPath
                );
            }

            return $token['access_token'];
        });
    }
}
