<?php

namespace Tests\Feature;

use App\Models\Agency;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/**
 * R9 — the Profile page (FR-04), Blade twin of PATCH /api/v1/me/profile.
 *
 * FR-04 is deliberately narrow: a user edits their OWN name, email and
 * password, with no approval and no notification. Two things therefore matter
 * as much as the happy path — that the edit cannot reach another account, and
 * that agency information stays read-only, since design decision 7 excludes an
 * agency-editing feature that no requirement backs.
 */
class WebProfilePageTest extends TestCase
{
    use RefreshDatabase;

    private Agency $agency;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->agency = Agency::factory()->create([
            'code' => 'BFP',
            'name' => 'Bureau of Fire Protection',
            'location' => 'Calbayog City',
            'contact_number' => '(055) 123-4567',
        ]);
        $this->admin = User::factory()->admin()->create([
            'agency_id' => $this->agency->id,
            'name' => 'Maria Santos',
            'email' => 'maria@rvms.local',
            'password' => 'password',
        ]);
    }

    /* -------------------------------- page -------------------------------- */

    public function test_the_page_shows_the_signed_in_admins_own_details(): void
    {
        $this->actingAs($this->admin)->get('/profile')->assertOk()
            ->assertSee('Maria Santos', false)
            ->assertSee('maria@rvms.local', false)
            ->assertSee('Bureau of Fire Protection', false)
            // The prototype's demo values must be gone.
            ->assertDontSee('admin@bfp.gov.ph', false)
            ->assertDontSee('value="Admin User"', false);
    }

    /** Design decision 7: agency identity is shown, never edited. */
    public function test_the_agency_fields_are_read_only(): void
    {
        $html = $this->actingAs($this->admin)->get('/profile')->assertOk()->getContent();

        foreach (['js-agency-name-input', 'js-agency-location', 'js-agency-contact'] as $hook) {
            $this->assertMatchesRegularExpression(
                '/'.$hook.'[^>]*\sdisabled/',
                $html,
                "{$hook} must be disabled — no FR backs editing agency information"
            );
        }

        // The prototype's logo button belongs to the same excluded feature.
        $this->assertMatchesRegularExpression('/Change Agency Logo/', $html);
        $this->assertMatchesRegularExpression('/disabled[^>]*>Change Agency Logo|Change Agency Logo/', $html);
    }

    /** The four editable fields are the ones FR-04 names, and only those. */
    public function test_only_the_own_account_fields_are_submittable(): void
    {
        $html = $this->actingAs($this->admin)->get('/profile')->assertOk()->getContent();

        foreach (['name="name"', 'name="email"', 'name="password"', 'name="password_confirmation"'] as $field) {
            $this->assertStringContainsString($field, $html);
        }

        $this->assertStringNotContainsString('name="agency_name"', $html);
        $this->assertStringNotContainsString('name="location"', $html);
        $this->assertStringNotContainsString('name="contact_number"', $html);
    }

    /* ------------------------------- updating ------------------------------ */

    public function test_an_admin_updates_their_own_name_and_email(): void
    {
        $this->actingAs($this->admin)
            ->patch('/profile', ['name' => 'Maria S. Santos', 'email' => 'maria.santos@rvms.local'])
            ->assertRedirect()
            ->assertSessionHas('status');

        $this->assertDatabaseHas('users', [
            'id' => $this->admin->id,
            'name' => 'Maria S. Santos',
            'email' => 'maria.santos@rvms.local',
        ]);
    }

    /** A blank password field means "keep the current one", not "clear it". */
    public function test_leaving_the_password_blank_keeps_the_current_one(): void
    {
        $before = $this->admin->password;

        $this->actingAs($this->admin)
            ->patch('/profile', ['name' => 'Maria Santos', 'email' => 'maria@rvms.local', 'password' => ''])
            ->assertRedirect();

        $this->assertSame($before, $this->admin->fresh()->password);
        $this->assertTrue(Hash::check('password', $this->admin->fresh()->password));
    }

    /**
     * Changing your own password rotates the session's remembered hash, which
     * would otherwise eject you on the very next request with no explanation.
     * The session must survive the edit and the new password must work.
     */
    public function test_changing_the_password_keeps_the_admin_signed_in(): void
    {
        $this->actingAs($this->admin)
            ->patch('/profile', [
                'name' => 'Maria Santos',
                'email' => 'maria@rvms.local',
                'password' => 'new-password-123',
                'password_confirmation' => 'new-password-123',
            ])
            ->assertRedirect();

        $this->assertTrue(Hash::check('new-password-123', $this->admin->fresh()->password));

        // Still signed in on the next request.
        $this->actingAs($this->admin->fresh())->get('/profile')->assertOk();
    }

    public function test_the_new_password_works_at_the_login_screen(): void
    {
        $this->actingAs($this->admin)->patch('/profile', [
            'name' => 'Maria Santos',
            'email' => 'maria@rvms.local',
            'password' => 'new-password-123',
            'password_confirmation' => 'new-password-123',
        ]);

        $this->post('/logout');
        $this->assertGuest();

        $this->post('/login', ['email' => 'maria@rvms.local', 'password' => 'new-password-123'])
            ->assertRedirect(route('dashboard'));
        $this->assertAuthenticatedAs($this->admin->fresh());
    }

    /* ------------------------------ validation ----------------------------- */

    public function test_a_mismatched_confirmation_is_rejected(): void
    {
        $this->actingAs($this->admin)
            ->from('/profile')
            ->patch('/profile', [
                'name' => 'Maria Santos',
                'email' => 'maria@rvms.local',
                'password' => 'new-password-123',
                'password_confirmation' => 'something-else',
            ])
            ->assertRedirect('/profile')
            ->assertSessionHasErrors('password');

        $this->assertTrue(Hash::check('password', $this->admin->fresh()->password));
    }

    public function test_a_short_password_is_rejected(): void
    {
        $this->actingAs($this->admin)->from('/profile')
            ->patch('/profile', ['password' => 'short', 'password_confirmation' => 'short'])
            ->assertSessionHasErrors('password');
    }

    /** Email is the login identifier, so it is globally unique. */
    public function test_an_email_already_in_use_is_rejected(): void
    {
        User::factory()->admin()->create([
            'agency_id' => $this->agency->id,
            'email' => 'taken@rvms.local',
        ]);

        $this->actingAs($this->admin)->from('/profile')
            ->patch('/profile', ['email' => 'taken@rvms.local'])
            ->assertSessionHasErrors('email');

        $this->assertSame('maria@rvms.local', $this->admin->fresh()->email);
    }

    /** Keeping your own email is not "taken". */
    public function test_resubmitting_your_own_email_is_allowed(): void
    {
        $this->actingAs($this->admin)
            ->patch('/profile', ['name' => 'Maria Santos', 'email' => 'maria@rvms.local'])
            ->assertSessionHasNoErrors();
    }

    /* -------------------------------- access ------------------------------- */

    /**
     * The route carries no user id at all — the edit always targets the
     * signed-in account, so a colleague's record cannot be reached even by
     * crafting the request.
     */
    public function test_the_edit_cannot_reach_another_admins_account(): void
    {
        $colleague = User::factory()->admin()->create([
            'agency_id' => $this->agency->id,
            'name' => 'Second Admin',
            'email' => 'second@rvms.local',
        ]);

        $this->actingAs($this->admin)
            ->patch('/profile', ['id' => $colleague->id, 'user_id' => $colleague->id, 'name' => 'Hijacked'])
            ->assertRedirect();

        $this->assertSame('Second Admin', $colleague->fresh()->name);
        $this->assertSame('Hijacked', $this->admin->fresh()->name);
    }

    public function test_a_guest_is_redirected(): void
    {
        $this->get('/profile')->assertRedirect(route('login'));
        $this->patch('/profile', ['name' => 'Nobody'])->assertRedirect(route('login'));
    }

    public function test_the_sidebar_and_dropdown_now_link_to_the_profile(): void
    {
        $this->actingAs($this->admin)->get('/dashboard')->assertOk()
            ->assertSee(route('profile'), false)
            ->assertDontSee('Available in a later phase (R9)', false);
    }
}
