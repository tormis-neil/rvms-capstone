<?php

namespace Tests\Feature;

use App\Models\Agency;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * `rvms:license-window` — the supported way to configure FR-08's threshold.
 *
 * The column existed and was described as configurable from the first
 * migration, but for a while nothing could write it: the on-screen control was
 * removed and no replacement went in, leaving `tinker` as the only answer. That
 * is a poor answer for something Chapter 4 calls configurable, and it stranded
 * CDRRMO on a 5-day window with no way back.
 *
 * The tests below fix the behaviour that matters at a handover: it must be
 * readable without changing anything, it must refuse a value that would make
 * the alert useless, and it must not be silently destructive.
 */
class LicenseWindowCommandTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Agency::factory()->create(['code' => 'BFP', 'name' => 'Bureau of Fire Protection', 'license_expiry_warning_days' => 30]);
        Agency::factory()->create(['code' => 'CDRRMO', 'name' => 'City Disaster Risk Reduction and Management Office', 'license_expiry_warning_days' => 5]);
    }

    private function window(string $code): int
    {
        return Agency::query()->where('code', $code)->firstOrFail()->license_expiry_warning_days;
    }

    /**
     * Run the command and hand back everything it printed.
     *
     * `$this->artisan()` matches each expectsOutputToContain against a separate
     * written line, so two expectations that land on the SAME line can never
     * both pass — and every row here is "code + number" on one line. Reading
     * the real output back is both simpler and a stronger assertion.
     */
    private function ran(string $command): string
    {
        \Illuminate\Support\Facades\Artisan::call($command);

        return \Illuminate\Support\Facades\Artisan::output();
    }

    /** The bare command answers "why does that agency say something different?". */
    public function test_it_lists_every_agencys_window_without_changing_anything(): void
    {
        $output = $this->ran('rvms:license-window');

        $this->assertStringContainsString('BFP', $output);
        $this->assertStringContainsString('30 days', $output);
        $this->assertStringContainsString('CDRRMO', $output);
        $this->assertStringContainsString('5 days', $output);

        $this->assertSame(30, $this->window('BFP'));
        $this->assertSame(5, $this->window('CDRRMO'));
    }

    /** Naming an agency with no number is still read-only. */
    public function test_naming_an_agency_alone_only_reports_it(): void
    {
        $output = $this->ran('rvms:license-window CDRRMO');

        $this->assertStringContainsString('5 days', $output);
        $this->assertSame(5, $this->window('CDRRMO'));
    }

    public function test_it_sets_the_window_after_confirmation(): void
    {
        $this->artisan('rvms:license-window CDRRMO 30')
            ->expectsConfirmation('Apply this change?', 'yes')
            ->assertSuccessful();

        $this->assertSame(30, $this->window('CDRRMO'));
    }

    /**
     * The change is announced before it happens, not after — the old window,
     * the new one, and who it affects. Run non-interactively here, which
     * answers the confirmation yes; the refusal path is the next test.
     */
    public function test_it_names_the_old_and_new_window_and_who_is_affected(): void
    {
        $output = $this->ran('rvms:license-window CDRRMO 45 --no-interaction');

        $this->assertStringContainsString('Current window  5 days', $output);
        $this->assertStringContainsString('New window      45 days', $output);
        $this->assertStringContainsString('Every administrator of CDRRMO is alerted', $output);
    }

    public function test_declining_the_confirmation_changes_nothing(): void
    {
        $this->artisan('rvms:license-window CDRRMO 30')
            ->expectsConfirmation('Apply this change?', 'no')
            ->assertSuccessful();

        $this->assertSame(5, $this->window('CDRRMO'));
    }

    /** Agency codes are typed by hand at a terminal; case is not the point. */
    public function test_the_agency_code_is_case_insensitive(): void
    {
        $this->artisan('rvms:license-window cdrrmo 30')
            ->expectsConfirmation('Apply this change?', 'yes')
            ->assertSuccessful();

        $this->assertSame(30, $this->window('CDRRMO'));
    }

    public function test_an_unknown_agency_is_refused_and_the_known_ones_listed(): void
    {
        $this->artisan('rvms:license-window CHO 30')
            ->expectsOutputToContain('No agency with the code "CHO"')
            ->expectsOutputToContain('CDRRMO')
            ->assertFailed();
    }

    /**
     * Zero would flag a licence only once it had already expired, which is the
     * one outcome FR-08 exists to prevent.
     */
    public function test_zero_days_is_refused(): void
    {
        $this->artisan('rvms:license-window CDRRMO 0')->assertFailed();

        $this->assertSame(5, $this->window('CDRRMO'));
    }

    /** The column is an unsigned smallint; a year is already a generous ceiling. */
    public function test_an_absurd_window_is_refused(): void
    {
        $this->artisan('rvms:license-window CDRRMO 4000')->assertFailed();

        $this->assertSame(5, $this->window('CDRRMO'));
    }

    /**
     * "30.5" and "-30" both survive an (int) cast into something plausible, so
     * the digits are checked rather than the cast.
     */
    public function test_a_non_whole_number_is_refused(): void
    {
        // Passed as an array, because Symfony's argv parser reads a leading
        // "-30" on the command line as an option rather than an argument.
        foreach (['30.5', '-30', 'thirty'] as $bad) {
            $this->artisan('rvms:license-window', ['agency' => 'CDRRMO', 'days' => $bad])
                ->assertFailed();
        }

        $this->assertSame(5, $this->window('CDRRMO'));
    }

    /** Re-running a deployment script must not prompt for an unchanged value. */
    public function test_setting_the_value_it_already_holds_is_a_no_op(): void
    {
        $this->artisan('rvms:license-window BFP 30')
            ->expectsOutputToContain('already set to 30 days')
            ->assertSuccessful();

        $this->assertSame(30, $this->window('BFP'));
    }

    /**
     * The whole point of the column: what the command writes is what the badge
     * and the daily alert then use (FR-08).
     */
    public function test_the_written_value_is_what_the_licence_check_reads(): void
    {
        $driver = \App\Models\User::factory()->driver()->create([
            'agency_id' => Agency::query()->where('code', 'CDRRMO')->firstOrFail()->id,
            'license_expiry_date' => now()->addDays(20)->toDateString(),
        ]);

        // At 5 days, a licence 20 days out is not yet a concern.
        $this->assertSame('Valid', $driver->fresh()->licenseStatus());

        $this->artisan('rvms:license-window CDRRMO 30')
            ->expectsConfirmation('Apply this change?', 'yes')
            ->assertSuccessful();

        $this->assertSame('Expiring Soon', $driver->fresh()->load('agency')->licenseStatus());
    }
}
