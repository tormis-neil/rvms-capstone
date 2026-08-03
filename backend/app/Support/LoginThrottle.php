<?php

namespace App\Support;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * Login throttling, shared by the web guard and the API (R10 sub-task 5, NFR-02).
 *
 * Both front doors open the same accounts, so throttling one and not the other
 * would leave the lock on the front door and the back door propped open. One
 * class, used by both.
 *
 * Keyed on **email + IP**, not IP alone: four agencies may sit behind one
 * office NAT, and a shared key would let one administrator's fat-fingered
 * password lock out their colleagues. The email is lower-cased and hashed into
 * the key so the rate-limit store never holds a readable address.
 *
 * A SUCCESSFUL login clears the counter, so the limit only ever counts
 * failures — an admin who mistypes twice and then gets it right starts fresh.
 */
class LoginThrottle
{
    /** Failures allowed before the door closes. */
    public const MAX_ATTEMPTS = 5;

    /** How long it stays closed, in seconds. */
    public const DECAY_SECONDS = 60;

    /**
     * Refuse early when the caller is already locked out.
     *
     * @throws ValidationException a 422 carrying the wait, matching how every
     *                             other credential failure is reported (the
     *                             framework surfaces it as 429 on the API).
     */
    public static function assertNotLocked(Request $request, string $email): void
    {
        $key = self::key($request, $email);

        if (! RateLimiter::tooManyAttempts($key, self::MAX_ATTEMPTS)) {
            return;
        }

        $seconds = RateLimiter::availableIn($key);

        throw ValidationException::withMessages([
            'email' => "Too many login attempts. Please try again in {$seconds} ".
                Str::plural('second', $seconds).'.',
        ])->status(429);
    }

    /** Record a failed attempt. */
    public static function recordFailure(Request $request, string $email): void
    {
        RateLimiter::hit(self::key($request, $email), self::DECAY_SECONDS);
    }

    /** Clear the counter after a successful sign-in. */
    public static function clear(Request $request, string $email): void
    {
        RateLimiter::clear(self::key($request, $email));
    }

    /**
     * Per-account, per-origin. Hashed so an attacker who reaches the cache
     * store cannot read back the addresses that have been tried.
     */
    private static function key(Request $request, string $email): string
    {
        return 'login:'.sha1(Str::lower(trim($email)).'|'.$request->ip());
    }
}
