<?php

namespace App\Jobs;

use App\Models\User;
use App\Services\Fcm\FcmMessage;
use App\Services\Fcm\FcmTransport;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

/**
 * Delivers one push off the request cycle (FR-21).
 *
 * Queued because Google is a third party: an admin reviewing a damage report
 * must not wait on, or fail because of, a slow FCM call. Transient failures
 * (429/5xx) throw and are retried with backoff; a rejected token is not a
 * failure and instead clears the stale token so the system stops trying.
 */
class SendFcmMessage implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    /** Seconds between retries — FCM rate limits recover quickly. */
    public array $backoff = [10, 60];

    public function __construct(
        private readonly FcmMessage $message,
        private readonly ?int $userId = null,
    ) {}

    public function handle(FcmTransport $transport): void
    {
        $delivered = $transport->send($this->message);

        if ($delivered || $this->userId === null) {
            return;
        }

        // The device is gone. Clear the token so later notifications skip it
        // instead of queueing a job per alert for a handset that no longer exists.
        User::query()
            ->whereKey($this->userId)
            ->where('fcm_token', $this->message->token)
            ->update(['fcm_token' => null]);
    }
}
