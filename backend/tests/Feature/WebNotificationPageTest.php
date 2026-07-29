<?php

namespace Tests\Feature;

use App\Models\Agency;
use App\Models\Notification;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * R7 Block B — the bell and the notifications page on live data.
 *
 * Block A left the prototype's hardcoded .html hrefs in place, which 404 under
 * real routing. The link assertions here exist so that cannot come back.
 */
class WebNotificationPageTest extends TestCase
{
    use RefreshDatabase;

    private Agency $agency;

    private User $admin;

    private User $secondAdmin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->agency = Agency::factory()->create(['code' => 'BFP']);
        $this->admin = User::factory()->admin()->create(['agency_id' => $this->agency->id]);
        $this->secondAdmin = User::factory()->admin()->create(['agency_id' => $this->agency->id]);
    }

    private function notificationFor(User $user, array $overrides = []): Notification
    {
        return Notification::factory()->create(array_merge([
            'agency_id' => $user->agency_id,
            'user_id' => $user->id,
        ], $overrides));
    }

    /* ------------------------------- the page ---------------------------- */

    public function test_the_page_lists_only_my_own_notifications(): void
    {
        $this->notificationFor($this->admin, ['message' => 'Mine to read']);
        $this->notificationFor($this->secondAdmin, ['message' => 'My colleagues inbox']);

        $this->actingAs($this->admin)
            ->get('/notifications')
            ->assertOk()
            ->assertSee('All alerts and submissions for your agency')
            ->assertSee('Mine to read')
            ->assertDontSee('My colleagues inbox');
    }

    public function test_the_page_groups_by_today_yesterday_and_earlier(): void
    {
        $this->notificationFor($this->admin, ['message' => 'Happened today']);
        $this->notificationFor($this->admin, ['message' => 'Happened yesterday', 'created_at' => now()->subDay()]);
        $this->notificationFor($this->admin, ['message' => 'Happened a while ago', 'created_at' => now()->subDays(6)]);

        $html = $this->actingAs($this->admin)->get('/notifications')->assertOk()->getContent();

        foreach (['Today', 'Yesterday', 'Earlier'] as $group) {
            $this->assertStringContainsString('>'.$group.'</h6>', $html);
        }

        // Groups appear in the prototype's order, newest first.
        $this->assertLessThan(strpos($html, '>Yesterday</h6>'), strpos($html, '>Today</h6>'));
        $this->assertLessThan(strpos($html, '>Earlier</h6>'), strpos($html, '>Yesterday</h6>'));
    }

    /** An empty group renders nothing at all (the prototype's own behaviour). */
    public function test_an_empty_group_is_not_rendered(): void
    {
        $this->notificationFor($this->admin, ['message' => 'Only today']);

        $html = $this->actingAs($this->admin)->get('/notifications')->assertOk()->getContent();

        $this->assertStringContainsString('>Today</h6>', $html);
        $this->assertStringNotContainsString('>Yesterday</h6>', $html);
        $this->assertStringNotContainsString('>Earlier</h6>', $html);
    }

    public function test_an_empty_inbox_shows_an_empty_state(): void
    {
        $this->actingAs($this->admin)
            ->get('/notifications')
            ->assertOk()
            ->assertSee('No notifications yet.', false);
    }

    /* ------------------------------ the links ---------------------------- */

    /**
     * Block A's rows pointed at inspections-damage.html, drivers.html, pm.html —
     * dead under Laravel routing. Every row must now post to a real route.
     */
    public function test_every_notification_row_posts_to_a_real_route(): void
    {
        $created = collect(Notification::TYPES)->map(
            fn (string $type) => $this->notificationFor($this->admin, ['type' => $type])
        );

        $html = $this->actingAs($this->admin)->get('/notifications')->assertOk()->getContent();

        $this->assertStringNotContainsString('.html', $html);

        foreach ($created as $notification) {
            $this->assertStringContainsString(
                route('notifications.open', $notification),
                $html,
                "Type {$notification->type} does not post to notifications.open."
            );
        }
    }

    /** Each type forwards to the module it concerns. */
    public function test_opening_a_notification_forwards_to_its_module(): void
    {
        $expected = [
            Notification::TYPE_NEW_DAMAGE_REPORT => route('inspections'),
            Notification::TYPE_INSPECTION_FLAGGED => route('inspections'),
            Notification::TYPE_NEW_ACCESS_REQUEST => route('drivers'),
            Notification::TYPE_LICENSE_EXPIRING => route('drivers'),
            Notification::TYPE_LICENSE_EXPIRED => route('drivers'),
            Notification::TYPE_PM_DUE_SOON => route('pm'),
            Notification::TYPE_PM_DUE => route('pm'),
            Notification::TYPE_PM_REMINDER => route('pm'),
            Notification::TYPE_VEHICLE_STATUS_UPDATE => route('vehicles'),
        ];

        // Every type is covered — a new type must not slip through untested.
        $this->assertSame(count(Notification::TYPES), count($expected));

        foreach ($expected as $type => $destination) {
            $notification = $this->notificationFor($this->admin, ['type' => $type]);

            $this->actingAs($this->admin)
                ->post(route('notifications.open', $notification))
                ->assertRedirect($destination);
        }
    }

    public function test_opening_a_notification_marks_it_read(): void
    {
        $notification = $this->notificationFor($this->admin);

        $this->actingAs($this->admin)
            ->post(route('notifications.open', $notification))
            ->assertRedirect(route('inspections'));

        $this->assertTrue($notification->fresh()->is_read);
        $this->assertNotNull($notification->fresh()->read_at);
    }

    public function test_a_colleague_cannot_open_my_notification(): void
    {
        $mine = $this->notificationFor($this->admin);

        $this->actingAs($this->secondAdmin)
            ->post(route('notifications.open', $mine))
            ->assertNotFound();

        $this->assertFalse($mine->fresh()->is_read);
    }

    /* ------------------------------ mark all ----------------------------- */

    public function test_mark_all_as_read_clears_only_my_own(): void
    {
        $this->notificationFor($this->admin);
        $this->notificationFor($this->admin);
        $colleagues = $this->notificationFor($this->secondAdmin);

        $this->actingAs($this->admin)
            ->from('/notifications')
            ->patch(route('notifications.read-all'))
            ->assertRedirect('/notifications')
            ->assertSessionHas('status');

        $this->assertSame(0, Notification::withoutGlobalScopes()
            ->where('user_id', $this->admin->id)->where('is_read', false)->count());
        $this->assertFalse($colleagues->fresh()->is_read);
    }

    /* -------------------------------- the bell --------------------------- */

    public function test_the_bell_shows_my_unread_count_on_every_page(): void
    {
        $this->notificationFor($this->admin, ['message' => 'Unread one']);
        $this->notificationFor($this->admin, ['message' => 'Unread two']);
        $this->notificationFor($this->admin, ['is_read' => true, 'read_at' => now()]);

        foreach (['/dashboard', '/vehicles', '/drivers', '/inspections', '/repairs', '/pm', '/dispatch'] as $page) {
            $html = $this->actingAs($this->admin)->get($page)->assertOk()->getContent();
            $this->assertStringContainsString('js-bell-count', $html, "No bell count on {$page}");
            $this->assertStringContainsString('>2 new<', $html, "Wrong unread count on {$page}");
        }
    }

    /** The prototype's bell lists Today and Yesterday only. */
    public function test_the_bell_lists_today_and_yesterday_only(): void
    {
        $this->notificationFor($this->admin, ['message' => 'Bell today']);
        $this->notificationFor($this->admin, ['message' => 'Bell yesterday', 'created_at' => now()->subDay()]);
        $this->notificationFor($this->admin, ['message' => 'Bell last week', 'created_at' => now()->subDays(6)]);

        $html = $this->actingAs($this->admin)->get('/dashboard')->assertOk()->getContent();

        $this->assertStringContainsString('Bell today', $html);
        $this->assertStringContainsString('Bell yesterday', $html);
        $this->assertStringNotContainsString('Bell last week', $html);
    }

    public function test_the_bell_hides_its_badge_when_nothing_is_unread(): void
    {
        $this->notificationFor($this->admin, ['is_read' => true, 'read_at' => now()]);

        $html = $this->actingAs($this->admin)->get('/dashboard')->assertOk()->getContent();

        $this->assertStringNotContainsString('js-bell-count', $html);
        $this->assertStringNotContainsString(' new<', $html);
    }

    public function test_the_bell_links_to_the_notifications_page(): void
    {
        $this->actingAs($this->admin)
            ->get('/dashboard')
            ->assertOk()
            ->assertSee('View All Notifications')
            ->assertSee(route('notifications'), false);
    }

    public function test_another_agency_sees_an_empty_bell(): void
    {
        $this->notificationFor($this->admin, ['message' => 'BFP business']);

        $other = Agency::factory()->create(['code' => 'CHO']);
        $otherAdmin = User::factory()->admin()->create(['agency_id' => $other->id]);

        $this->actingAs($otherAdmin)
            ->get('/dashboard')
            ->assertOk()
            ->assertDontSee('BFP business')
            ->assertSee('No notifications yet.', false);
    }

    public function test_guests_are_redirected(): void
    {
        $this->get('/notifications')->assertRedirect(route('login'));
    }

    /* ----------------------------- clear read ---------------------------- */

    /**
     * Clearing removes only what the admin has already read. An alert nobody
     * has opened yet is exactly the one FR-21 promised to deliver, so a stray
     * click must not be able to destroy it.
     */
    public function test_clearing_removes_read_notifications_and_keeps_unread_ones(): void
    {
        $read = $this->notificationFor($this->admin, [
            'is_read' => true, 'read_at' => now(), 'message' => 'Already seen',
        ]);
        $unread = $this->notificationFor($this->admin, ['message' => 'Still waiting']);

        $this->actingAs($this->admin)
            ->delete(route('notifications.clear-read'))
            ->assertRedirect();

        $this->assertDatabaseMissing('notifications', ['id' => $read->id]);
        $this->assertDatabaseHas('notifications', ['id' => $unread->id]);
    }

    /** An agency may have several admins; one clearing must not empty another's. */
    public function test_clearing_never_touches_another_admins_inbox(): void
    {
        $mine = $this->notificationFor($this->admin, ['is_read' => true, 'read_at' => now()]);
        $theirs = $this->notificationFor($this->secondAdmin, ['is_read' => true, 'read_at' => now()]);

        $this->actingAs($this->admin)->delete(route('notifications.clear-read'));

        $this->assertDatabaseMissing('notifications', ['id' => $mine->id]);
        $this->assertDatabaseHas('notifications', ['id' => $theirs->id]);
    }

    public function test_clearing_never_touches_another_agency(): void
    {
        $other = Agency::factory()->create(['code' => 'CHO']);
        $otherAdmin = User::factory()->admin()->create(['agency_id' => $other->id]);
        $theirs = $this->notificationFor($otherAdmin, ['is_read' => true, 'read_at' => now()]);

        $this->notificationFor($this->admin, ['is_read' => true, 'read_at' => now()]);

        $this->actingAs($this->admin)->delete(route('notifications.clear-read'));

        $this->assertDatabaseHas('notifications', ['id' => $theirs->id]);
    }

    /** Nothing to clear is a no-op that still says so, never an error. */
    public function test_clearing_an_inbox_with_nothing_read_is_harmless(): void
    {
        $unread = $this->notificationFor($this->admin);

        $this->actingAs($this->admin)
            ->delete(route('notifications.clear-read'))
            ->assertRedirect()
            ->assertSessionHas('status', 'There were no read notifications to clear.');

        $this->assertDatabaseHas('notifications', ['id' => $unread->id]);
    }

    /** The control is offered only when it would do something. */
    public function test_the_clear_button_is_disabled_with_nothing_read(): void
    {
        $this->notificationFor($this->admin);

        $html = $this->actingAs($this->admin)->get('/notifications')->assertOk()->getContent();

        $this->assertStringContainsString('Clear Read', $html);
        $this->assertMatchesRegularExpression('/clearReadModal"[^>]*\sdisabled/', $html);
    }

    public function test_the_clear_button_is_enabled_once_something_is_read(): void
    {
        $this->notificationFor($this->admin, ['is_read' => true, 'read_at' => now()]);

        $html = $this->actingAs($this->admin)->get('/notifications')->assertOk()->getContent();

        $this->assertDoesNotMatchRegularExpression('/clearReadModal"[^>]*\sdisabled/', $html);
        // The confirmation states what is about to be destroyed.
        $this->assertStringContainsString('1 read notification', $html);
    }

    public function test_a_guest_cannot_clear_notifications(): void
    {
        $this->delete(route('notifications.clear-read'))->assertRedirect(route('login'));
    }
}
