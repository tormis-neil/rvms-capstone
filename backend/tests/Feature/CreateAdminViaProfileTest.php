<?php

namespace Tests\Feature;

use App\Models\Agency;
use App\Models\Notification;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * An administrator provisions another administrator for their own agency from
 * the Profile page (design decision 6 revised, 2026-09).
 *
 * The capability moved from the rvms:create-admin server command to an in-app,
 * authenticated path. The safeguards are the point: own agency only, the acting
 * admin confirms their own password, and the agency's existing admins are told.
 */
class CreateAdminViaProfileTest extends TestCase
{
    use RefreshDatabase;

    private Agency $agency;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->agency = Agency::factory()->create(['code' => 'BFP']);
        $this->admin = User::factory()->admin()->create([
            'agency_id' => $this->agency->id,
            'email' => 'admin@rvms.local',
            'password' => 'password',
        ]);
    }

    /** @return array<string, string> */
    private function payload(array $overrides = []): array
    {
        return array_merge([
            'admin_name' => 'New Officer',
            'admin_email' => 'new.officer@rvms.local',
            'admin_password' => 'secret-password',
            'admin_password_confirmation' => 'secret-password',
            'current_password' => 'password',
        ], $overrides);
    }

    public function test_an_admin_creates_an_admin_for_their_own_agency(): void
    {
        $this->actingAs($this->admin)
            ->from('/profile')
            ->post('/profile/administrators', $this->payload())
            ->assertRedirect('/profile');

        $created = User::query()->where('email', 'new.officer@rvms.local')->first();

        $this->assertNotNull($created);
        $this->assertSame(User::ROLE_ADMIN, $created->role);
        $this->assertSame(User::STATUS_ACTIVE, $created->status);
        $this->assertSame($this->agency->id, $created->agency_id);
    }

    public function test_the_new_admin_can_sign_in(): void
    {
        $this->actingAs($this->admin)->post('/profile/administrators', $this->payload());
        $this->post('/logout');

        $this->post('/login', ['email' => 'new.officer@rvms.local', 'password' => 'secret-password'])
            ->assertRedirect(route('dashboard'));
    }

    public function test_the_agency_is_forced_to_the_actors_regardless_of_input(): void
    {
        $other = Agency::factory()->create(['code' => 'PNP']);

        // Even if an agency_id is smuggled in, it is ignored — the field is never read.
        $this->actingAs($this->admin)
            ->post('/profile/administrators', $this->payload(['agency_id' => $other->id]));

        $this->assertSame(
            $this->agency->id,
            User::query()->where('email', 'new.officer@rvms.local')->value('agency_id'),
        );
    }

    public function test_a_wrong_current_password_is_refused(): void
    {
        $this->actingAs($this->admin)
            ->from('/profile')
            ->post('/profile/administrators', $this->payload(['current_password' => 'not-my-password']))
            ->assertRedirect('/profile')
            ->assertSessionHasErrors('current_password');

        $this->assertDatabaseMissing('users', ['email' => 'new.officer@rvms.local']);
    }

    public function test_a_duplicate_email_is_refused(): void
    {
        $this->actingAs($this->admin)
            ->post('/profile/administrators', $this->payload(['admin_email' => 'admin@rvms.local']))
            ->assertSessionHasErrors('admin_email');

        // Only the original admin exists under that email.
        $this->assertSame(1, User::query()->where('email', 'admin@rvms.local')->count());
    }

    public function test_a_mismatched_password_confirmation_is_refused(): void
    {
        $this->actingAs($this->admin)
            ->post('/profile/administrators', $this->payload(['admin_password_confirmation' => 'different']))
            ->assertSessionHasErrors('admin_password');

        $this->assertDatabaseMissing('users', ['email' => 'new.officer@rvms.local']);
    }

    public function test_the_existing_admins_are_notified_but_not_the_actor_or_the_new_admin(): void
    {
        $peer = User::factory()->admin()->create([
            'agency_id' => $this->agency->id,
            'email' => 'peer@rvms.local',
        ]);

        $this->actingAs($this->admin)->post('/profile/administrators', $this->payload());

        $created = User::query()->where('email', 'new.officer@rvms.local')->first();

        // The existing peer admin is told.
        $this->assertDatabaseHas('notifications', [
            'user_id' => $peer->id,
            'type' => Notification::TYPE_NEW_ADMIN,
        ]);

        // The actor (who just did it) and the new account (its subject) are not.
        $this->assertDatabaseMissing('notifications', [
            'user_id' => $this->admin->id,
            'type' => Notification::TYPE_NEW_ADMIN,
        ]);
        $this->assertDatabaseMissing('notifications', [
            'user_id' => $created->id,
            'type' => Notification::TYPE_NEW_ADMIN,
        ]);
    }

    public function test_a_driver_cannot_reach_the_route(): void
    {
        $driver = User::factory()->driver()->create(['agency_id' => $this->agency->id]);

        $this->actingAs($driver)
            ->post('/profile/administrators', $this->payload())
            ->assertForbidden();

        $this->assertDatabaseMissing('users', ['email' => 'new.officer@rvms.local']);
    }

    public function test_a_guest_is_redirected_to_login(): void
    {
        $this->post('/profile/administrators', $this->payload())
            ->assertRedirect(route('login'));

        $this->assertDatabaseMissing('users', ['email' => 'new.officer@rvms.local']);
    }

    public function test_the_profile_page_lists_the_agencys_admins(): void
    {
        $peer = User::factory()->admin()->create([
            'agency_id' => $this->agency->id,
            'name' => 'Logistics Officer',
        ]);
        $foreign = User::factory()->admin()->create([
            'agency_id' => Agency::factory()->create(['code' => 'CHO'])->id,
            'name' => 'Other Agency Admin',
        ]);

        $this->actingAs($this->admin)
            ->get('/profile')
            ->assertOk()
            ->assertSee('Agency Administrators')
            ->assertSee('Logistics Officer')
            ->assertDontSee('Other Agency Admin');
    }
}
