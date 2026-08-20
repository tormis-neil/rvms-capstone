<?php

namespace Tests\Feature;

use App\Models\Agency;
use App\Models\User;
use App\Models\Vehicle;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/**
 * Provisioning an administrator has to be possible after installation.
 *
 * Design decision 6 says administrator accounts are provisioned rather than
 * self-registered, and that is right — an administrator sees every record in
 * their agency. But until this command existed, "provisioned" meant "created by
 * the seeder at install and never again": an agency that appointed a second
 * officer had no supported path, and the workaround people reach for,
 * `migrate:fresh --seed`, erases every real record in the database.
 */
class CreateAdminCommandTest extends TestCase
{
    use RefreshDatabase;

    private Agency $agency;

    protected function setUp(): void
    {
        parent::setUp();

        $this->agency = Agency::factory()->create(['code' => 'BFP', 'name' => 'Bureau of Fire Protection']);
    }

    public function test_it_provisions_an_active_administrator(): void
    {
        $this->artisan('rvms:create-admin', [
            '--agency' => 'BFP',
            '--name' => 'Logistics Officer',
            '--email' => 'logistics@bfp.local',
        ])
            ->expectsQuestion('Password (min 8 characters)', 'a-strong-password')
            ->assertExitCode(0);

        $admin = User::query()->where('email', 'logistics@bfp.local')->firstOrFail();

        $this->assertSame(User::ROLE_ADMIN, $admin->role);
        $this->assertSame($this->agency->id, $admin->agency_id);
        // Provisioned, so there is nothing to approve — unlike a self-registered
        // driver, who starts pending (FR-03).
        $this->assertSame(User::STATUS_ACTIVE, $admin->status);
    }

    public function test_the_password_is_hashed_and_actually_works(): void
    {
        $this->artisan('rvms:create-admin', [
            '--agency' => 'BFP', '--name' => 'Ops Officer', '--email' => 'ops@bfp.local',
        ])->expectsQuestion('Password (min 8 characters)', 'a-strong-password');

        $admin = User::query()->where('email', 'ops@bfp.local')->firstOrFail();

        $this->assertNotSame('a-strong-password', $admin->password, 'The password was stored in plain text.');
        $this->assertTrue(Hash::check('a-strong-password', $admin->password));

        // The point of the account: it can sign in to the dashboard.
        $this->post('/login', ['email' => 'ops@bfp.local', 'password' => 'a-strong-password'])
            ->assertRedirect('/dashboard');
    }

    public function test_a_duplicate_email_is_refused(): void
    {
        User::factory()->admin()->create(['agency_id' => $this->agency->id, 'email' => 'taken@bfp.local']);

        // No password prompt: the email is rejected first, so nobody is asked to
        // type a secret for an account that was never going to be created.
        $this->artisan('rvms:create-admin', [
            '--agency' => 'BFP', '--name' => 'Someone Else', '--email' => 'taken@bfp.local',
        ])->assertExitCode(1);

        $this->assertSame(1, User::query()->where('email', 'taken@bfp.local')->count());
    }

    public function test_an_unknown_agency_is_refused_and_lists_the_real_ones(): void
    {
        $this->artisan('rvms:create-admin', [
            '--agency' => 'NOPE', '--name' => 'X', '--email' => 'x@bfp.local',
        ])
            ->expectsOutputToContain('No agency with the code')
            ->expectsOutputToContain('BFP')
            ->assertExitCode(1);

        $this->assertDatabaseMissing('users', ['email' => 'x@bfp.local']);
    }

    public function test_a_short_password_is_refused(): void
    {
        $this->artisan('rvms:create-admin', [
            '--agency' => 'BFP', '--name' => 'Short Pass', '--email' => 'short@bfp.local',
        ])
            ->expectsQuestion('Password (min 8 characters)', 'short')
            ->assertExitCode(1);

        $this->assertDatabaseMissing('users', ['email' => 'short@bfp.local']);
    }

    /**
     * A provisioned administrator arrives to a full dashboard, not an empty one
     * (2026-08, lead-reported as a question — the answer is "no, and here is
     * the proof").
     *
     * Worth asserting because the intuition runs the other way: a brand-new
     * account looks like it should start empty. Access here is scoped by
     * AGENCY, not by who created the record, so an officer appointed next year
     * sees every vehicle, driver and inspection the agency already has — which
     * is the whole point of design decision 6 allowing more than one
     * administrator per agency. MultiAdminAgencyTest proves the scope rule
     * against a fixture model; this proves it against the real pages, with the
     * account made the way an administrator is actually made.
     */
    public function test_a_provisioned_admin_sees_the_agencys_existing_records(): void
    {
        $incumbent = User::factory()->admin()->create(['agency_id' => $this->agency->id]);
        $driver = User::factory()->driver()->create([
            'agency_id' => $this->agency->id,
            'name' => 'Ramon Villanueva',
        ]);
        Vehicle::factory()->create([
            'agency_id' => $this->agency->id,
            'plate_number' => 'ABC-1234',
            'assigned_driver_id' => $driver->id,
        ]);

        $this->artisan('rvms:create-admin', [
            '--agency' => 'BFP', '--name' => 'New Officer', '--email' => 'new@bfp.local',
        ])->expectsQuestion('Password (min 8 characters)', 'a-strong-password');

        $new = User::query()->where('email', 'new@bfp.local')->firstOrFail();

        $this->actingAs($new)->get('/vehicles')->assertOk()->assertSee('ABC-1234');
        $this->actingAs($new)->get('/drivers')->assertOk()->assertSee('Ramon Villanueva');

        // The dashboard counters are computed from the same agency-scoped
        // queries, so the new administrator's read of the fleet is the
        // incumbent's read of the fleet — not a fresh, empty one.
        $this->assertSame(
            $this->dashboardCounters($incumbent),
            $this->dashboardCounters($new),
            'A newly provisioned administrator saw different dashboard figures than the incumbent.'
        );
    }

    /** The numbers printed on the dashboard's metric cards, in order. */
    private function dashboardCounters(User $admin): array
    {
        $html = $this->actingAs($admin)->get('/dashboard')->assertOk()->getContent();

        preg_match_all('/<h2[^>]*>\s*([0-9]+)\s*<\/h2>/', $html, $matches);

        return $matches[1];
    }
}
