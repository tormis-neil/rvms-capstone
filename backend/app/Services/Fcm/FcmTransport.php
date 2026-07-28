<?php

namespace App\Services\Fcm;

/**
 * How a push actually leaves the system (FR-21).
 *
 * Behind an interface so the real Google HTTP v1 call is swapped for a log
 * writer in local development (no Firebase credentials needed to build and
 * test every trigger) and for a recorder in the test suite.
 */
interface FcmTransport
{
    /**
     * @return bool true when the device accepted delivery. A stale or
     *              unregistered token returns false rather than throwing, so
     *              one dead handset never fails a whole batch.
     */
    public function send(FcmMessage $message): bool;
}
