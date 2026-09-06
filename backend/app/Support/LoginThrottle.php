<?php

namespace App\Support;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
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
 *
 * **Sliding window (2026-09).** Every attempt while the account is failing —
 * including one that is already locked out — pushes the unlock time
 * DECAY_SECONDS into the future. The door therefore reopens only after
 * DECAY_SECONDS with NO attempt at all. The earlier version let Laravel's
 * RateLimiter start a fixed window at the FIRST failure and never extend it, so
 * an attacker who paced one guess every twelve seconds kept five live guesses a
 * minute forever; and the countdown, measured from that first failure, read
 * several seconds short of the real wait. A window that resets on each attempt
 * closes both.
 */
class LoginThrottle
{
    /** Failures allowed before the door closes. */
    public const MAX_ATTEMPTS = 5;

    /** How long the door stays closed after the LAST attempt, in seconds. */
    public const DECAY_SECONDS = 60;

    /**
     * Refuse early when the caller is already locked out.
     *
     * A blocked attempt is itself activity, so it slides the window forward
     * before refusing — trickling guesses can never keep the count just under
     * the limit, because each one resets the clock.
     *
     * @throws ValidationException a 422 carrying the wait, matching how every
     *                             other credential failure is reported (the
     *                             framework surfaces it as 429 on the API).
     */
    public static function assertNotLocked(Request $request, string $email): void
    {
        $key = self::key($request, $email);

        if (self::attempts($key) < self::MAX_ATTEMPTS) {
            return;
        }

        // Slide the window: this blocked attempt keeps the door shut for a fresh
        // DECAY_SECONDS, so the countdown below is always the true remaining wait.
        self::touch($key);

        throw ValidationException::withMessages([
            'email' => 'Too many login attempts. Please try again in '.self::DECAY_SECONDS.' '.
                Str::plural('second', self::DECAY_SECONDS).'.',
        ])->status(429);
    }

    /** Record a failed attempt, resetting the decay window from now. */
    public static function recordFailure(Request $request, string $email): void
    {
        self::touch(self::key($request, $email));
    }

    /** Clear the counter after a successful sign-in. */
    public static function clear(Request $request, string $email): void
    {
        Cache::forget(self::key($request, $email));
    }

    /** How many failures are on record for this key right now. */
    private static function attempts(string $key): int
    {
        return (int) Cache::get($key, 0);
    }

    /**
     * Add one to the counter and (re)start the decay window from now.
     *
     * Writing with a fresh TTL on every call is what makes the window slide:
     * the counter — and therefore the lockout — only expires after
     * DECAY_SECONDS with no further attempt. A plain increment would keep the
     * TTL of the first write, which is the fixed window this replaces.
     */
    private static function touch(string $key): void
    {
        Cache::put($key, self::attempts($key) + 1, self::DECAY_SECONDS);
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
