<?php

namespace App\Jobs;

use App\Models\User;
use App\Services\Fcm\FcmConfigurationException;
use App\Services\Fcm\FcmMessage;
use App\Services\Fcm\FcmTransport;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Delivers one push off the request cycle (FR-21).
 *
 * Queued because Google is a third party: an admin reviewing a damage report
 * must not wait on, or fail because of, a slow FCM call. Transient failures
 * (429/5xx) throw and are retried with backoff; a rejected token is not a
 * failure and instead clears the stale token so the system stops trying.
 *
 * The job runs in two modes, and handle() behaves differently in each:
 *
 *  - **After the response** (how NotificationDispatcher sends it, so a
 *    deployment needs no queue worker). There is no retry machinery here, so a
 *    transient failure is handed to the QUEUE, which the scheduler drains every
 *    minute. Late beats lost.
 *  - **Under a worker** (the re-dispatched copy above, or anything queued
 *    normally). Transient failures rethrow into the usual $tries/$backoff.
 *
 * `$this->job` is what tells them apart: it is set only by a queue worker.
 */
class SendFcmMessage implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    /** Seconds between retries — FCM rate limits recover quickly. */
    public array $backoff = [10, 60];

    /**
     * Stop retrying after ten minutes regardless of attempts (R10 sub-task 7).
     *
     * An alert says a vehicle's status changed NOW. A push that finally lands
     * an hour later, after a worker restart replays a stale queue, is worse
     * than one that never lands: the row is already in the driver's inbox, so
     * nothing is lost by giving up, and a late banner about a status that has
     * since changed again is actively misleading.
     */
    public function retryUntil(): \DateTimeInterface
    {
        return now()->addMinutes(10);
    }

    /*
     | Deliberately NOT ShouldBeUnique (R10 sub-task 7).
     |
     | Deduplicating identical pushes was considered and rejected: the
     | duplicate it would prevent is already impossible, because
     | VehicleStatusWriter notifies only when the status actually CHANGES, and
     | every other alert names its own record. What it could do instead is
     | suppress a legitimate second alert that happens to read identically —
     | a real loss traded for a theoretical gain. A uniqueId() without the
     | interface would be worse still: dead code that looks like a guarantee.
     */

    public function __construct(
        private readonly FcmMessage $message,
        private readonly ?int $userId = null,
    ) {}

    public function handle(FcmTransport $transport): void
    {
        try {
            $delivered = $transport->send($this->message);
        } catch (FcmConfigurationException $e) {
            // Nothing about the next two attempts would differ, and three
            // identical failures in the worker read like a flaky network when
            // the real answer is one line of setup. Say it once, plainly, and
            // stop.
            Log::error('FCM is misconfigured — no push can be delivered until this is fixed. '
                .$e->getMessage());

            $this->fail($e);

            return;
        } catch (Throwable $e) {
            // A transient failure — Google was slow, unreachable, or rate
            // limiting. Under a worker this is what $tries and $backoff are
            // for, so rethrow and let them do their job.
            if ($this->job !== null) {
                throw $e;
            }

            // Running AFTER THE RESPONSE, where there is no retry machinery at
            // all: the callback runs once and whatever it loses is lost. That
            // was the accepted cost of needing no queue worker, and on a slow
            // connection it is paid often enough to notice — the alert reaches
            // the inbox while the banner never arrives (2026-08,
            // lead-reported).
            //
            // So hand it to the queue instead of dropping it. The scheduler
            // already drains that queue every minute (`queue:work
            // --stop-when-empty` in routes/console.php), so the retry needs
            // nothing running that a deployment does not already have. The
            // re-dispatched copy runs under a worker, takes the branch above,
            // and gets the normal three attempts with backoff.
            //
            // Worst case the push is a minute late instead of never. Nothing
            // here can loop: a queued attempt always has $this->job set.
            Log::warning('FCM push failed after the response; queued for retry. '.$e->getMessage());

            self::dispatch($this->message, $this->userId);

            return;
        }

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
