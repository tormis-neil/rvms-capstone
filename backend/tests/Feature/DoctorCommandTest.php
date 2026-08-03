<?php

namespace Tests\Feature;

use App\Models\Agency;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * R10 sub-task 6 — the deployment check (NFR-01…NFR-05).
 *
 * The command exists because the things it checks are invisible from the
 * developer's laptop: the laptop has the queue worker and the scheduler
 * running in other windows, so a handover that is missing both looks
 * identical to one that is complete.
 *
 * Two properties matter as much as the individual checks: it must report
 * EVERY finding in one run rather than dying at the first, and its exit code
 * must be non-zero on failure so it can be trusted in a checklist.
 */
class DoctorCommandTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $agency = Agency::factory()->create(['code' => 'BFP']);
        User::factory()->admin()->create(['agency_id' => $agency->id]);
    }

    public function test_it_checks_the_things_that_silently_break_a_handover(): void
    {
        $this->artisan('rvms:doctor')
            ->expectsOutputToContain('APP_KEY')
            ->expectsOutputToContain('APP_TIMEZONE')
            ->expectsOutputToContain('Database connection')
            ->expectsOutputToContain('QUEUE_CONNECTION')
            ->expectsOutputToContain('rvms:recalculate-pm')
            ->expectsOutputToContain('rvms:pm-alerts')
            ->expectsOutputToContain('rvms:license-alerts');
    }

    /**
     * Registration is only half of it — something must CALL the scheduler.
     * There is no portable way to read the host's crontab, so the command
     * states the requirement rather than silently passing.
     */
    public function test_it_states_the_cron_requirement_it_cannot_test(): void
    {
        // Captured rather than chained through expectsOutputToContain: the
        // hint is wrapped at 100 characters, so the phrase spans lines and the
        // chained matcher is line-oriented. Asserting on the whole buffer is
        // what this test actually means.
        Artisan::call('rvms:doctor');
        $output = Artisan::output();

        $this->assertStringContainsString('schedule:run', $output);
        $this->assertStringContainsString('NEVER', $output);
    }

    /** A wrong timezone dates every pre-8am record to the previous day. */
    public function test_a_non_manila_timezone_fails(): void
    {
        config(['app.timezone' => 'UTC']);

        $this->artisan('rvms:doctor')
            ->expectsOutputToContain('APP_TIMEZONE is UTC')
            ->assertExitCode(1);
    }

    /** Stack traces with database credentials, on a machine an agency can reach. */
    public function test_debug_mode_outside_local_fails(): void
    {
        $this->app['env'] = 'production';
        config(['app.debug' => true]);

        $this->artisan('rvms:doctor')
            ->expectsOutputToContain('APP_DEBUG is ON outside development')
            ->assertExitCode(1);
    }

    /**
     * In a development environment APP_DEBUG is correct, so it must WARN
     * rather than fail.
     *
     * The exit code is deliberately not asserted: it also reflects unrelated
     * checks (whether this checkout happens to have run storage:link), and
     * coupling the test to those would make it fail for reasons that have
     * nothing to do with debug mode.
     */
    public function test_debug_mode_locally_is_only_a_warning(): void
    {
        config(['app.debug' => true, 'app.timezone' => 'Asia/Manila']);

        Artisan::call('rvms:doctor');
        $output = Artisan::output();

        // 'testing' is a development environment exactly as 'local' is —
        // debug there is a warning, never a failure.
        $this->assertStringContainsString('APP_DEBUG is on (APP_ENV=testing)', $output);
        $this->assertStringNotContainsString('APP_DEBUG is ON outside development', $output);
    }

    /** Without storage:link every damage photo 404s (FR-11). */
    public function test_a_missing_storage_link_fails(): void
    {
        $link = public_path('storage');

        if (file_exists($link)) {
            $this->markTestSkipped('storage:link already present in this checkout.');
        }

        $this->artisan('rvms:doctor')
            ->expectsOutputToContain('public/storage link is missing')
            ->assertExitCode(1);
    }

    /** Nobody can sign in to a dashboard with no administrators. */
    public function test_it_fails_when_no_administrator_exists(): void
    {
        User::query()->delete();

        $this->artisan('rvms:doctor')
            ->expectsOutputToContain('No administrator accounts exist')
            ->assertExitCode(1);
    }

    /** The seeded demo password on a reachable machine is an open door. */
    public function test_the_seeded_demo_password_is_flagged(): void
    {
        $agency = Agency::factory()->create(['code' => 'PNP']);
        User::factory()->admin()->create(['agency_id' => $agency->id, 'password' => 'password']);

        $this->artisan('rvms:doctor')
            ->expectsOutputToContain('seeded demo password');
    }

    /** A backlog is what "no queue worker is running" looks like from the database. */
    public function test_a_large_undrained_queue_fails(): void
    {
        config(['queue.default' => 'database']);

        foreach (range(1, 25) as $i) {
            DB::table('jobs')->insert([
                'queue' => 'default', 'payload' => '{}', 'attempts' => 0,
                'reserved_at' => null, 'available_at' => time(), 'created_at' => time(),
            ]);
        }

        $this->artisan('rvms:doctor')
            ->expectsOutputToContain('not draining')
            ->assertExitCode(1);
    }

    /** A clean deployment passes with exit code 0. */
    public function test_a_healthy_deployment_exits_zero(): void
    {
        config(['app.debug' => false, 'app.timezone' => 'Asia/Manila']);

        if (! file_exists(public_path('storage'))) {
            $this->markTestSkipped('storage:link is not present in this checkout.');
        }

        $this->artisan('rvms:doctor')->assertExitCode(0);
    }
}
