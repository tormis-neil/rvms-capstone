<?php

namespace Tests\Feature;

use App\Models\Agency;
use App\Models\Notification;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Notification inbox + device token (FR-21).
 *
 * A notification is addressed to ONE user, so agency scoping alone is not
 * enough: an agency may have several administrators, and one must never read
 * another's inbox.
 */
class NotificationApiTest extends TestCase
{
    use RefreshDatabase;

    private Agency $agency;

    private User $admin;

    private User $secondAdmin;

    private User $driver;

    protected function setUp(): void
    {
        parent::setUp();
        $this->agency = Agency::factory()->create(['code' => 'BFP']);
        $this->admin = User::factory()->admin()->create(['agency_id' => $this->agency->id]);
        $this->secondAdmin = User::factory()->admin()->create(['agency_id' => $this->agency->id]);
        $this->driver = User::factory()->driver()->create(['agency_id' => $this->agency->id]);
    }

    private function notificationFor(User $user, array $overrides = []): Notification
    {
        return Notification::factory()->create(array_merge([
            'agency_id' => $user->agency_id,
            'user_id' => $user->id,
        ], $overrides));
    }

    public function test_a_user_sees_only_their_own_notifications(): void
    {
        $this->notificationFor($this->admin, ['message' => 'Mine']);
        $this->notificationFor($this->secondAdmin, ['message' => 'My colleagues']);

        $response = $this->actingAs($this->admin, 'sanctum')
            ->getJson('/api/v1/notifications')
            ->assertOk();

        $response->assertJsonCount(1, 'data');
        $this->assertSame('Mine', $response->json('data.0.message'));
    }

    public function test_another_agency_is_never_visible(): void
    {
        $other = Agency::factory()->create(['code' => 'PNP']);
        $otherAdmin = User::factory()->admin()->create(['agency_id' => $other->id]);
        $this->notificationFor($otherAdmin, ['message' => 'PNP business']);

        $this->actingAs($this->admin, 'sanctum')
            ->getJson('/api/v1/notifications')
            ->assertOk()
            ->assertJsonCount(0, 'data');
    }

    public function test_the_payload_carries_the_presentation_fields(): void
    {
        $this->notificationFor($this->admin, ['type' => Notification::TYPE_PM_DUE]);

        $this->actingAs($this->admin, 'sanctum')
            ->getJson('/api/v1/notifications')
            ->assertOk()
            ->assertJsonPath('data.0.icon', 'bi-wrench-adjustable')
            ->assertJsonPath('data.0.tone', 'danger')
            ->assertJsonPath('data.0.group', 'Today');
    }

    public function test_the_unread_count_is_returned_and_filterable(): void
    {
        $this->notificationFor($this->admin);
        $this->notificationFor($this->admin);
        $this->notificationFor($this->admin, ['is_read' => true, 'read_at' => now()]);

        $this->actingAs($this->admin, 'sanctum')
            ->getJson('/api/v1/notifications')
            ->assertOk()
            ->assertJsonCount(3, 'data')
            ->assertJsonPath('meta.unread_count', 2);

        $this->actingAs($this->admin, 'sanctum')
            ->getJson('/api/v1/notifications?unread=1')
            ->assertOk()
            ->assertJsonCount(2, 'data');
    }

    /**
     * A resource array containing a `data` key makes Laravel skip its own
     * `data` envelope, so single resources came back unwrapped while
     * collections stayed wrapped. The payload is exposed as `payload` to keep
     * one shape on both endpoints.
     */
    public function test_single_and_collection_responses_share_one_envelope(): void
    {
        $notification = $this->notificationFor($this->admin);

        $this->actingAs($this->admin, 'sanctum')
            ->getJson('/api/v1/notifications')
            ->assertOk()
            ->assertJsonPath('data.0.payload.plate', 'ABC-1234');

        $this->actingAs($this->admin, 'sanctum')
            ->patchJson("/api/v1/notifications/{$notification->id}/read")
            ->assertOk()
            ->assertJsonPath('data.payload.plate', 'ABC-1234')
            ->assertJsonMissingPath('data.data');
    }

    public function test_marking_read_is_idempotent(): void
    {
        $notification = $this->notificationFor($this->admin);

        $this->actingAs($this->admin, 'sanctum')
            ->patchJson("/api/v1/notifications/{$notification->id}/read")
            ->assertOk()
            ->assertJsonPath('data.is_read', true);

        $firstReadAt = $notification->fresh()->read_at;

        // Re-reading must not move the original timestamp.
        $this->travel(5)->minutes();
        $this->actingAs($this->admin, 'sanctum')
            ->patchJson("/api/v1/notifications/{$notification->id}/read")
            ->assertOk();

        $this->assertEquals($firstReadAt, $notification->fresh()->read_at);
    }

    /** The case agency scoping alone would let through. */
    public function test_a_colleague_cannot_mark_my_notification_read(): void
    {
        $mine = $this->notificationFor($this->admin);

        $this->actingAs($this->secondAdmin, 'sanctum')
            ->patchJson("/api/v1/notifications/{$mine->id}/read")
            ->assertNotFound();

        $this->assertFalse($mine->fresh()->is_read);
    }

    public function test_read_all_clears_only_my_own(): void
    {
        $this->notificationFor($this->admin);
        $this->notificationFor($this->admin);
        $colleagues = $this->notificationFor($this->secondAdmin);

        $this->actingAs($this->admin, 'sanctum')
            ->patchJson('/api/v1/notifications/read-all')
            ->assertOk()
            ->assertJsonPath('data.marked_read', 2)
            ->assertJsonPath('data.unread_count', 0);

        $this->assertFalse($colleagues->fresh()->is_read);
    }

    public function test_a_driver_has_their_own_inbox(): void
    {
        $this->notificationFor($this->driver, [
            'type' => Notification::TYPE_VEHICLE_STATUS_UPDATE,
            'message' => 'ABC-1234 is now Not Operational.',
        ]);

        $this->actingAs($this->driver, 'sanctum')
            ->getJson('/api/v1/notifications')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.type', Notification::TYPE_VEHICLE_STATUS_UPDATE);
    }

    public function test_notifications_require_authentication(): void
    {
        $this->getJson('/api/v1/notifications')->assertUnauthorized();
        $this->patchJson('/api/v1/notifications/read-all')->assertUnauthorized();
    }

    public function test_a_driver_registers_and_clears_a_device_token(): void
    {
        $this->actingAs($this->driver, 'sanctum')
            ->postJson('/api/v1/fcm-token', ['fcm_token' => 'device-abc'])
            ->assertOk()
            ->assertJsonPath('data.registered', true);

        $this->assertSame('device-abc', $this->driver->fresh()->fcm_token);

        $this->actingAs($this->driver, 'sanctum')
            ->deleteJson('/api/v1/fcm-token')
            ->assertOk();

        $this->assertNull($this->driver->fresh()->fcm_token);
    }

    public function test_an_empty_token_is_rejected(): void
    {
        $this->actingAs($this->driver, 'sanctum')
            ->postJson('/api/v1/fcm-token', ['fcm_token' => ''])
            ->assertStatus(422)
            ->assertJsonValidationErrors('fcm_token');
    }

    /**
     * A handset passed to another driver must stop receiving the previous
     * driver's notifications.
     */
    public function test_registering_a_token_takes_it_from_its_previous_owner(): void
    {
        $previous = User::factory()->driver()->create([
            'agency_id' => $this->agency->id,
            'fcm_token' => 'shared-handset',
        ]);

        $this->actingAs($this->driver, 'sanctum')
            ->postJson('/api/v1/fcm-token', ['fcm_token' => 'shared-handset'])
            ->assertOk();

        $this->assertNull($previous->fresh()->fcm_token);
        $this->assertSame('shared-handset', $this->driver->fresh()->fcm_token);
    }
}
