<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Services\Fcm\FcmTransportFactory;
use App\Services\Fcm\LogFcmTransport;
use Illuminate\Console\Command;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Throwable;

/**
 * Checks that a DEPLOYED RVMS is actually complete (R10 sub-task 6).
 *
 * `rvms:fcm-doctor` earned its keep by turning "FAIL and a duration" into one
 * named cause. This is the same idea widened to the handover: the things that
 * are invisible until an agency needs them, and that all look identical from
 * the developer's laptop because the laptop has them running in other windows.
 *
 * Every check answers one question — would this break in front of the client?
 * Failures are things that WILL bite; warnings are things that will bite on a
 * real server but are correct locally, so the command reads honestly whether
 * it is run on a demo laptop or a deployment.
 */
class Doctor extends Command
{
    protected $signature = 'rvms:doctor';

    protected $description = 'Check that this RVMS deployment is complete and safe to hand over';

    private int $failures = 0;

    private int $warnings = 0;

    /**
     * Later checks read tables, so they are skipped when the connection is
     * down. A deployment check must report EVERY finding in one run — dying on
     * the first real problem is precisely the behaviour it exists to replace.
     */
    private bool $databaseUp = false;

    public function handle(): int
    {
        $this->components->info('RVMS — deployment check');

        $this->checkEnvironment();
        $this->checkDatabase();
        $this->checkStorageLink();
        $this->checkQueue();
        $this->checkScheduler();
        $this->checkNotificationDelivery();
        $this->checkAccounts();

        $this->newLine();

        if ($this->failures > 0) {
            $this->components->error(
                $this->failures.' '.str('check')->plural($this->failures).' failed'.
                ($this->warnings > 0 ? ", {$this->warnings} warning(s)" : '').
                ' — this deployment is not ready to hand over.'
            );

            return self::FAILURE;
        }

        if ($this->warnings > 0) {
            $this->components->warn($this->warnings.' warning(s) — fine locally, read them before deploying.');

            return self::SUCCESS;
        }

        $this->components->info('All checks passed.');

        return self::SUCCESS;
    }

    /* ------------------------------ the checks ---------------------------- */

    private function checkEnvironment(): void
    {
        $env = app()->environment();
        $debug = config('app.debug');

        // APP_DEBUG on a machine an agency can reach puts stack traces —
        // including database credentials — on screen for anyone who triggers
        // an error (security audit ticket T7).
        if ($debug && ! $this->isDevelopment()) {
            $this->failed('APP_DEBUG is ON outside development', "APP_ENV={$env}. Stack traces expose database credentials. Set APP_DEBUG=false.");
        } elseif ($debug) {
            $this->warning("APP_DEBUG is on (APP_ENV={$env})", 'Correct for development. It MUST be false on the deployment machine.');
        } else {
            $this->pass('APP_DEBUG', 'off');
        }

        if (blank(config('app.key'))) {
            $this->failed('APP_KEY is empty', 'Run `php artisan key:generate`. Sessions and encrypted values cannot work without it.');
        } else {
            $this->pass('APP_KEY', 'set');
        }

        // The agencies operate in Philippine time. Under UTC, anything logged
        // before 8 AM Manila is filed on the PREVIOUS day — a 7:30 AM
        // inspection reads "Yesterday" and damage reports carry the wrong date.
        $tz = config('app.timezone');
        $tz === 'Asia/Manila'
            ? $this->pass('APP_TIMEZONE', $tz)
            : $this->failed("APP_TIMEZONE is {$tz}", 'Must be Asia/Manila, or records before 8 AM are dated to the previous day.');

        if (file_exists(base_path('bootstrap/cache/config.php'))) {
            $this->warning('Config is cached', 'Remember `php artisan config:clear` after any .env change, or edits are silently ignored.');
        }
    }

    private function checkDatabase(): void
    {
        try {
            DB::connection()->getPdo();
        } catch (Throwable $e) {
            $this->failed('Database unreachable', $e->getMessage().' — checks that read tables are skipped below.');

            return;
        }

        $this->databaseUp = true;
        $this->pass('Database connection', config('database.default'));

        // A missing table means migrations were never run on this machine —
        // everything else would fail in a far more confusing way.
        foreach (['users', 'vehicles', 'notifications', 'jobs', 'cache'] as $table) {
            if (! Schema::hasTable($table)) {
                $this->failed("Table `{$table}` is missing", 'Run `php artisan migrate`.');

                return;
            }
        }

        $this->pass('Schema', 'all expected tables present');
    }

    /**
     * Without `storage:link` EVERY damage photo 404s — the feature looks
     * broken in exactly the way a demo will surface (FR-11).
     */
    private function checkStorageLink(): void
    {
        $link = public_path('storage');

        if (! file_exists($link)) {
            $this->failed('public/storage link is missing', 'Run `php artisan storage:link`, or every damage photo will 404.');

            return;
        }

        $this->pass('public/storage link', 'present');

        try {
            Storage::disk('public')->put('.rvms-doctor', 'ok');
            Storage::disk('public')->delete('.rvms-doctor');
            $this->pass('Storage is writable', 'yes');
        } catch (Throwable $e) {
            $this->failed('Storage is not writable', $e->getMessage());
        }
    }

    /**
     * Pushes are queued, so with no worker they sit in `jobs` forever and the
     * only symptom is a phone that never buzzes (FR-21).
     */
    private function checkQueue(): void
    {
        $connection = config('queue.default');

        if ($connection === 'sync') {
            $this->warning('QUEUE_CONNECTION=sync', 'Pushes send inline, so an admin waits on Google. Use `database` in deployment.');

            return;
        }

        $this->pass('QUEUE_CONNECTION', $connection);

        if (! $this->databaseUp || ! Schema::hasTable('jobs')) {
            return;
        }

        $pending = DB::table('jobs')->count();
        $failed = Schema::hasTable('failed_jobs') ? DB::table('failed_jobs')->count() : 0;

        // A backlog that is not draining is what "no worker is running" looks
        // like from the database — the check a laptop cannot otherwise make.
        $pending > 20
            ? $this->failed("{$pending} jobs are queued and not draining", 'Is `php artisan queue:work` running (supervised) on this machine?')
            : $this->pass('Queued jobs', (string) $pending);

        if ($failed > 0) {
            $this->warning("{$failed} failed job(s)", 'Inspect with `php artisan queue:failed`; clear with `php artisan queue:flush`.');
        }
    }

    /**
     * The single easiest way to hand over a system that looks complete and
     * silently is not: no cron entry, so nothing time-driven ever fires.
     */
    private function checkScheduler(): void
    {
        $expected = ['rvms:recalculate-pm', 'rvms:pm-alerts', 'rvms:license-alerts'];
        $registered = collect(app()->make(Schedule::class)->events())
            ->map(fn ($event) => $event->command ?? '')
            ->implode(' ');

        foreach ($expected as $command) {
            str_contains($registered, $command)
                ? $this->pass("Scheduled: {$command}", 'registered')
                : $this->failed("{$command} is not scheduled", 'Check routes/console.php or the scheduler registration.');
        }

        // Registration is only half of it — something must CALL the scheduler.
        // No portable way to read the host's crontab, so this is stated rather
        // than tested, because a silent pass here would be the worst outcome.
        $this->hint(
            'The scheduler only runs if the OS calls it. Confirm the deployment machine has: '.
            '`* * * * * cd '.base_path().' && php artisan schedule:run >> /dev/null 2>&1` '.
            '(Linux) or the equivalent Task Scheduler entry every 1 minute (Windows). '.
            'Without it, licence and PM alerts NEVER fire.'
        );
    }

    private function checkNotificationDelivery(): void
    {
        $factory = app(FcmTransportFactory::class);
        $transport = $factory->make();

        if ($transport instanceof LogFcmTransport) {
            // Not a failure: running without Firebase is a supported state —
            // rows are still written and still readable in the app.
            $this->warning('Push is simulated', ($factory->fallbackReason() ?? '').' Run `php artisan rvms:fcm-doctor` for detail.');

            return;
        }

        $this->pass('Push delivery', 'live (FcmHttpV1Transport)');
        $this->hint('Run `php artisan rvms:fcm-doctor` to verify Google actually answers.');
    }

    private function checkAccounts(): void
    {
        if (! $this->databaseUp || ! Schema::hasTable('users')) {
            return;
        }

        $admins = User::query()->where('role', User::ROLE_ADMIN)->count();

        $admins === 0
            ? $this->failed('No administrator accounts exist', 'Run `php artisan db:seed`, or nobody can sign in to the dashboard.')
            : $this->pass('Administrator accounts', (string) $admins);

        // The seeded demo password on a reachable machine is an open door.
        $demoPasswords = User::query()->get()
            ->filter(fn (User $user) => Hash::check('password', $user->password))
            ->count();

        if ($demoPasswords > 0 && ! $this->isDevelopment()) {
            $this->failed("{$demoPasswords} account(s) still use the seeded demo password", 'Change them before the system is reachable by anyone else.');
        } elseif ($demoPasswords > 0) {
            $this->warning("{$demoPasswords} account(s) use the seeded demo password", 'Expected locally; must be changed before deployment.');
        }
    }

    /**
     * One definition of "a developer's machine", shared by every check that
     * needs it.
     *
     * Both the debug-mode check and the demo-password check ask this question,
     * and they used to answer it differently: one accepted `local` and
     * `testing`, the other only `local`. Under `APP_ENV=testing` the seeded
     * password therefore became a hard failure, so the command reported a
     * clean deployment as broken — caught by `a healthy deployment exits zero`,
     * which only runs on a checkout that has had `storage:link` run. Answering
     * it in one place is what stops the two drifting apart again.
     */
    private function isDevelopment(): bool
    {
        return in_array(app()->environment(), ['local', 'testing'], true);
    }

    /* ------------------------------- output ------------------------------- */

    private function pass(string $label, string $detail): void
    {
        $this->components->twoColumnDetail($label, "<fg=green>{$detail}</>");
    }

    private function warning(string $label, string $detail): void
    {
        $this->warnings++;
        $this->components->twoColumnDetail($label, '<fg=yellow>warning</>');
        $this->hint($detail);
    }

    private function failed(string $label, string $detail): void
    {
        $this->failures++;
        $this->components->twoColumnDetail($label, '<fg=red>FAILED</>');
        $this->hint($detail);
    }

    private function hint(string $text): void
    {
        $this->line('  <fg=cyan>→</> '.wordwrap($text, 100, PHP_EOL.'    '));
    }
}
