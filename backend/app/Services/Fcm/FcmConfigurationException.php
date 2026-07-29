<?php

namespace App\Services\Fcm;

use RuntimeException;

/**
 * A push failed for a reason no retry can fix (FR-21).
 *
 * The distinction matters in the queue: a 429 or a 5xx from Google is worth
 * three attempts with backoff, but a service-account key that cannot mint a
 * token, a Firebase project whose Cloud Messaging API is switched off, or a
 * machine that cannot verify Google's TLS certificate will fail identically
 * every time. Those are reported once, loudly, and the job is failed
 * immediately instead of being retried into the same wall three times over.
 */
class FcmConfigurationException extends RuntimeException {}
