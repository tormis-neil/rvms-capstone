<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;

use function Laravel\Prompts\password as promptPassword;

/**
 * The last resort when nobody can sign in (FR-22, 2026-08).
 *
 * Two of the three recovery paths are in the dashboard: an administrator resets
 * a driver, and administrators reset each other. This exists for the case
 * neither covers — an agency whose ONLY administrator has forgotten their
 * password. Without it, the answer is "call the developer and have them edit
 * the database", which is not an answer you can write in a handover document.
 *
 * Deliberately a console command rather than a screen: it needs no
 * authentication, so it must only be reachable by someone already standing at
 * the server. That is the whole security model, and it is the right one — the
 * alternative is an unauthenticated reset endpoint, which is a back door.
 */
class ResetPassword extends Command
{
    protected $signature = 'rvms:reset-password {email? : The account to reset}';

    protected $description = 'Set a new password for any account — the recovery path when nobody can sign in';

    public function handle(): int
    {
        $email = $this->argument('email') ?: $this->ask('Email address');

        $user = User::query()->where('email', $email)->first();

        if (! $user) {
            $this->components->error("No account found for {$email}.");

            $this->line('  Known administrators:');
            User::query()->where('role', User::ROLE_ADMIN)->orderBy('email')
                ->each(fn (User $admin) => $this->line("  <fg=cyan>-</> {$admin->email}"));

            return self::FAILURE;
        }

        $password = promptPassword('New password (min 8 characters)');

        if (strlen($password) < 8) {
            $this->components->error('Password must be at least 8 characters.');

            return self::FAILURE;
        }

        $user->update(['password' => $password]);

        // Any session opened with the old password is no longer trustworthy —
        // a reset is often prompted by a device nobody controls any more.
        $revoked = $user->tokens()->count();
        $user->tokens()->delete();

        $this->components->info("Password updated for {$user->name} ({$user->email}).");

        if ($revoked > 0) {
            $this->components->warn("{$revoked} existing device token(s) revoked — they must sign in again.");
        }

        return self::SUCCESS;
    }
}
