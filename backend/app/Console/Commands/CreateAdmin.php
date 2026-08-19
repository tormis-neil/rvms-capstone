<?php

namespace App\Console\Commands;

use App\Models\Agency;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Validator;

use function Laravel\Prompts\password as promptPassword;

/**
 * Provision an Agency Administrator account (design decision 6).
 *
 * Administrator accounts are provisioned rather than self-registered: only
 * drivers may sign up, and their account waits for an administrator's approval.
 * That rule is deliberate and stated in Chapter 1 — an administrator can see
 * every record in their agency, so the account cannot be self-serve.
 *
 * But "provisioned" has to MEAN something. Until now the only administrators
 * that ever existed were the ones the seeder created at install, so an agency
 * that hires a second officer next year had no supported path at all: the
 * answer was to edit the database by hand, or re-run the seeder, and
 * `migrate:fresh --seed` erases every real record in the process. That is not
 * an answer that belongs in a handover document.
 *
 * Deliberately a console command, for the same reason as rvms:reset-password:
 * it needs no authentication, so it must only be reachable by someone already
 * standing at the server. A screen for this would be a way to mint an
 * administrator from the internet, which is precisely what design decision 6
 * rules out.
 */
class CreateAdmin extends Command
{
    protected $signature = 'rvms:create-admin
                            {--agency= : Agency code (BFP, PNP, CDRRMO, CHO)}
                            {--name= : Full name}
                            {--email= : Email address, used to sign in}';

    protected $description = 'Provision an Agency Administrator account (design decision 6)';

    public function handle(): int
    {
        $this->components->info('RVMS — provision an administrator');

        $agency = $this->resolveAgency();

        if (! $agency) {
            return self::FAILURE;
        }

        $name = $this->option('name') ?: $this->ask('Full name');
        $email = $this->option('email') ?: $this->ask('Email address');

        $validator = Validator::make(
            ['name' => $name, 'email' => $email],
            [
                'name' => ['required', 'string', 'max:255'],
                // Email is the login identifier and is unique across the whole
                // system, not per agency — two people cannot share a sign-in.
                'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            ]
        );

        if ($validator->fails()) {
            foreach ($validator->errors()->all() as $message) {
                $this->components->error($message);
            }

            return self::FAILURE;
        }

        $password = promptPassword('Password (min 8 characters)');

        if (strlen($password) < 8) {
            $this->components->error('The password must be at least 8 characters.');

            return self::FAILURE;
        }

        $admin = User::query()->create([
            'agency_id' => $agency->id,
            'role' => User::ROLE_ADMIN,
            'name' => $name,
            'email' => $email,
            'password' => $password,
            // Administrators are provisioned, so there is nothing to approve —
            // unlike a self-registered driver, who starts pending (FR-03).
            'status' => User::STATUS_ACTIVE,
        ]);

        $this->newLine();
        $this->components->info("Administrator created for {$agency->code}.");
        $this->components->twoColumnDetail('Name', $admin->name);
        $this->components->twoColumnDetail('Email', $admin->email);
        $this->components->twoColumnDetail('Agency', "{$agency->code} — {$agency->name}");
        $this->newLine();
        $this->line('  <fg=cyan>→</> Give them the password directly. The system does not send email.');

        return self::SUCCESS;
    }

    /** The agency this administrator belongs to, or null with the reason printed. */
    private function resolveAgency(): ?Agency
    {
        $codes = Agency::query()->orderBy('code')->pluck('name', 'code')->all();

        if ($codes === []) {
            $this->components->error('No agencies exist yet. Run `php artisan db:seed` first.');

            return null;
        }

        $code = $this->option('agency') ?: $this->choice('Agency', array_keys($codes));
        $agency = Agency::query()->where('code', strtoupper((string) $code))->first();

        if (! $agency) {
            $this->components->error("No agency with the code \"{$code}\".");
            $this->line('  Known agencies:');
            foreach ($codes as $known => $name) {
                $this->line("  <fg=cyan>-</> {$known} — {$name}");
            }

            return null;
        }

        return $agency;
    }
}
