<?php

namespace App\Services\Fcm;

use Illuminate\Support\Facades\Log;

/**
 * The local-development sender: writes the push to the log instead of calling
 * Google.
 *
 * This is what makes every FR-21 trigger buildable and testable without
 * Firebase credentials — the notification row is still written to the database
 * and still appears on the bell and the notifications page; only the push
 * itself is simulated. Selected automatically whenever the credentials are
 * absent, so a missing key degrades to "no push" rather than an exception on
 * every damage report.
 */
class LogFcmTransport implements FcmTransport
{
    public function send(FcmMessage $message): bool
    {
        Log::info('FCM push (simulated — no Firebase credentials configured)', [
            'token' => substr($message->token, 0, 12).'…',
            'title' => $message->title,
            'body' => $message->body,
            'data' => $message->data,
        ]);

        return true;
    }
}
