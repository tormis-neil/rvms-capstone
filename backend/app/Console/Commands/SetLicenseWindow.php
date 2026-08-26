<?php

namespace App\Console\Commands;

use App\Models\Agency;
use Illuminate\Console\Command;

/**
 * Set an agency's licence warning window (FR-08).
 *
 * FR-08 says the number of days before expiry at which a licence is flagged is
 * configured PER AGENCY, and `agencies.license_expiry_warning_days` is that
 * configuration — a column rather than a constant, deliberately (CLAUDE.md,
 * "Configurable thresholds live in DB columns").
 *
 * Until now nothing could write it. An on-screen control was tried on the
 * Drivers page and removed (lead-reported, 2026-08): an administrator changing
 * it daily is not a real workflow, and the control invited exactly the accident
 * that produced this command — a value typed while looking around the screen,
 * left behind, and then unreachable. CDRRMO sat on a 5-day window for weeks
 * because of it, which is not a cosmetic difference: `SendLicenseAlerts` reads
 * the same column, so its administrators had five days' notice of a lapsing
 * licence where every other agency had thirty.
 *
 * So the setting keeps its column and gets a console command instead, matching
 * rvms:create-admin and rvms:reset-password: deployment-time configuration,
 * reachable only by someone standing at the server, with no screen to fat-finger
 * during a demo. The Drivers page states the resulting rule in a sentence so an
 * administrator can still see what "Expiring Soon" means.
 */
class SetLicenseWindow extends Command
{
    /**
     * Both arguments optional so the bare command is a safe way to LOOK: with
     * no agency it lists every window, which is the question actually asked
     * most often ("why does this agency say something different?").
     */
    protected $signature = 'rvms:license-window
                            {agency? : Agency code (BFP, PNP, CDRRMO, CHO)}
                            {days? : Days before expiry to flag a licence as Expiring Soon}';

    protected $description = "Show or set an agency's licence expiry warning window (FR-08)";

    /** A window has to be long enough to act on and short enough to mean something. */
    private const MINIMUM_DAYS = 1;

    private const MAXIMUM_DAYS = 365;

    public function handle(): int
    {
        if (Agency::query()->count() === 0) {
            $this->components->error('No agencies exist yet. Run `php artisan db:seed` first.');

            return self::FAILURE;
        }

        // No agency named: report every window and stop. Read-only on purpose.
        if ($this->argument('agency') === null) {
            return $this->listWindows();
        }

        $agency = $this->resolveAgency();

        if (! $agency) {
            return self::FAILURE;
        }

        // Named an agency but no number: show just that one, still read-only.
        if ($this->argument('days') === null) {
            $this->describe($agency);
            $this->newLine();
            $this->line('  <fg=cyan>→</> To change it: <options=bold>php artisan rvms:license-window '
                .$agency->code.' 30</>');

            return self::SUCCESS;
        }

        return $this->apply($agency);
    }

    /** Every agency's window, so an odd one is visible next to the others. */
    private function listWindows(): int
    {
        $this->components->info('Licence expiry warning windows (FR-08)');

        foreach (Agency::query()->orderBy('code')->get() as $agency) {
            $this->describe($agency);
        }

        $this->newLine();
        $this->line('  <fg=cyan>→</> To change one: <options=bold>php artisan rvms:license-window CDRRMO 30</>');

        return self::SUCCESS;
    }

    /**
     * Deliberately `line()` rather than `components->twoColumnDetail()`: the
     * two-column renderer pads to the terminal width, so a long agency name
     * pushes the number to the far edge and the reader's eye has to travel the
     * dots to pair them up. Four fixed-width rows read at a glance.
     */
    private function describe(Agency $agency): void
    {
        $this->line(sprintf(
            '  <fg=cyan>-</> %-8s %3d days   <fg=gray>%s</>',
            $agency->code,
            $agency->license_expiry_warning_days,
            $agency->name
        ));
    }

    private function apply(Agency $agency): int
    {
        $raw = (string) $this->argument('days');

        // ctype_digit rather than is_numeric: "30.5" and "-30" both cast to a
        // plausible-looking int, and the column is an unsigned smallint.
        if (! ctype_digit($raw)) {
            $this->components->error("\"{$raw}\" is not a whole number of days.");

            return self::FAILURE;
        }

        $days = (int) $raw;

        if ($days < self::MINIMUM_DAYS || $days > self::MAXIMUM_DAYS) {
            $this->components->error(sprintf(
                'The window must be between %d and %d days. %d is outside that.',
                self::MINIMUM_DAYS, self::MAXIMUM_DAYS, $days
            ));

            return self::FAILURE;
        }

        $current = $agency->license_expiry_warning_days;

        if ($days === $current) {
            $this->components->info("{$agency->code} is already set to {$days} days. Nothing changed.");

            return self::SUCCESS;
        }

        $this->newLine();
        $this->line("  Agency          {$agency->code} — {$agency->name}");
        $this->line("  Current window  {$current} days");
        $this->line("  New window      <fg=green>{$days} days</>");
        $this->newLine();
        $this->line("  Licences will be flagged <options=bold>Expiring Soon</> {$days} days before they expire.");
        $this->line("  Every administrator of {$agency->code} is alerted daily while that applies.");
        $this->newLine();

        // --no-interaction answers yes, which is what a deployment script needs;
        // a person at a terminal is still asked, because this silently changes
        // how much warning an agency gets about a lapsing licence.
        if (! $this->confirm('Apply this change?', true)) {
            $this->components->warn('Cancelled. Nothing changed.');

            return self::SUCCESS;
        }

        $agency->update(['license_expiry_warning_days' => $days]);

        $this->components->info("{$agency->code} now flags licences {$days} days before expiry.");

        return self::SUCCESS;
    }

    /** The agency named on the command line, or null with the reason printed. */
    private function resolveAgency(): ?Agency
    {
        $code = strtoupper((string) $this->argument('agency'));
        $agency = Agency::query()->where('code', $code)->first();

        if (! $agency) {
            $this->components->error("No agency with the code \"{$code}\".");
            $this->line('  Known agencies:');
            foreach (Agency::query()->orderBy('code')->get() as $known) {
                $this->line("  <fg=cyan>-</> {$known->code} — {$known->name}");
            }

            return null;
        }

        return $agency;
    }
}
