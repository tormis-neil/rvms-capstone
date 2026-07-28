<?php

namespace Tests\Feature;

use App\Jobs\SendFcmMessage;
use App\Models\Agency;
use App\Models\Notification;
use App\Models\User;
use App\Services\Fcm\FakeFcmTransport;
use App\Services\Fcm\FcmHttpV1Transport;
use App\Services\Fcm\FcmMessage;
use App\Services\Fcm\FcmTransport;
use App\Services\Fcm\LogFcmTransport;
use App\Services\NotificationDispatcher;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

/**
 * Push delivery (FR-21): the transport, the queued job, and the dispatcher that
 * raises a stored row plus a best-effort push.
 */
class FcmTransportTest extends TestCase
{
    use RefreshDatabase;

    private Agency $agency;

    private User $admin;

    private User $driver;

    protected function setUp(): void
    {
        parent::setUp();
        $this->agency = Agency::factory()->create(['code' => 'BFP']);
        $this->admin = User::factory()->admin()->create(['agency_id' => $this->agency->id]);
        $this->driver = User::factory()->driver()->create([
            'agency_id' => $this->agency->id,
            'fcm_token' => 'device-abc',
        ]);
    }

    /** No credentials configured must degrade to the log, never throw. */
    public function test_the_log_transport_is_used_without_credentials(): void
    {
        config(['services.fcm.project_id' => null, 'services.fcm.credentials' => null]);
        $this->app->forgetInstance(FcmTransport::class);

        $this->assertInstanceOf(LogFcmTransport::class, $this->app->make(FcmTransport::class));
    }

    /** A configured but unreadable key path must also degrade, not blow up. */
    public function test_an_unreadable_credentials_path_falls_back_to_the_log(): void
    {
        config([
            'services.fcm.project_id' => 'rvms-28129',
            'services.fcm.credentials' => 'storage/app/does-not-exist.json',
        ]);
        $this->app->forgetInstance(FcmTransport::class);

        $this->assertInstanceOf(LogFcmTransport::class, $this->app->make(FcmTransport::class));
    }

    public function test_the_real_transport_is_used_when_credentials_are_readable(): void
    {
        $path = storage_path('app/fake-credentials.json');
        file_put_contents($path, json_encode(['type' => 'service_account']));

        config([
            'services.fcm.project_id' => 'rvms-28129',
            'services.fcm.credentials' => 'storage/app/fake-credentials.json',
        ]);
        $this->app->forgetInstance(FcmTransport::class);

        try {
            $this->assertInstanceOf(FcmHttpV1Transport::class, $this->app->make(FcmTransport::class));
        } finally {
            @unlink($path);
        }
    }

    /** FCM rejects a data payload containing non-strings. */
    public function test_the_payload_matches_the_http_v1_shape(): void
    {
        $payload = (new FcmMessage(
            token: 'device-abc',
            title: 'PM Due',
            body: 'EFG-4532 has reached its due mileage.',
            data: ['notification_id' => 42, 'plate' => 'EFG-4532'],
        ))->toPayload();

        $this->assertSame('device-abc', $payload['message']['token']);
        $this->assertSame('PM Due', $payload['message']['notification']['title']);
        $this->assertSame('42', $payload['message']['data']['notification_id']);
        $this->assertSame('high', $payload['message']['android']['priority']);
    }

    public function test_raising_a_notification_stores_a_row_and_queues_a_push(): void
    {
        Queue::fake();

        $notification = app(NotificationDispatcher::class)->send(
            $this->driver,
            Notification::TYPE_VEHICLE_STATUS_UPDATE,
            'Vehicle Status Updated',
            'ABC-1234 is now Not Operational.',
            ['plate' => 'ABC-1234'],
        );

        $this->assertDatabaseHas('notifications', [
            'id' => $notification->id,
            'user_id' => $this->driver->id,
            'agency_id' => $this->agency->id,
            'type' => Notification::TYPE_VEHICLE_STATUS_UPDATE,
            'is_read' => false,
        ]);

        Queue::assertPushed(SendFcmMessage::class, 1);
    }

    /** A user with no registered device still gets the stored row. */
    public function test_no_device_token_means_no_push_but_still_a_row(): void
    {
        Queue::fake();

        app(NotificationDispatcher::class)->send(
            $this->admin, // seeded without a token
            Notification::TYPE_NEW_DAMAGE_REPORT,
            'New Damage Report Submitted',
            'ABC-1234 — cracked mirror.',
        );

        $this->assertDatabaseCount('notifications', 1);
        Queue::assertNothingPushed();
    }

    /** FR-21 addresses ALL of an agency's admins, not just the first. */
    public function test_sending_to_many_reaches_every_admin_of_the_agency(): void
    {
        Queue::fake();
        $second = User::factory()->admin()->create(['agency_id' => $this->agency->id]);

        // A different agency's admin, and an inactive one, must be excluded.
        $other = Agency::factory()->create(['code' => 'PNP']);
        User::factory()->admin()->create(['agency_id' => $other->id]);

        $dispatcher = app(NotificationDispatcher::class);
        $admins = $dispatcher->adminsOf($this->agency->id);
        $dispatcher->sendToMany($admins, Notification::TYPE_NEW_DAMAGE_REPORT, 'New Damage Report Submitted', 'ABC-1234 — cracked mirror.');

        $this->assertCount(2, $admins);
        $this->assertDatabaseCount('notifications', 2);
        $this->assertDatabaseHas('notifications', ['user_id' => $this->admin->id]);
        $this->assertDatabaseHas('notifications', ['user_id' => $second->id]);
    }

    /**
     * A scheduled command runs with no authenticated user, so the row must take
     * the RECIPIENT's agency rather than relying on the auto-stamp.
     */
    public function test_a_notification_raised_with_no_authenticated_user_keeps_the_recipient_agency(): void
    {
        Queue::fake();
        $this->assertGuest();

        app(NotificationDispatcher::class)->send(
            $this->driver,
            Notification::TYPE_PM_REMINDER,
            'Preventive Maintenance',
            'ABC-1234 — oil change due soon.',
        );

        $this->assertDatabaseHas('notifications', [
            'user_id' => $this->driver->id,
            'agency_id' => $this->agency->id,
        ]);
    }

    public function test_the_job_sends_through_the_transport(): void
    {
        $fake = new FakeFcmTransport;
        $this->app->instance(FcmTransport::class, $fake);

        (new SendFcmMessage(new FcmMessage('device-abc', 'Title', 'Body'), $this->driver->id))
            ->handle($fake);

        $this->assertSame(1, $fake->count());
        $this->assertCount(1, $fake->sentTo('device-abc'));
        $this->assertSame('device-abc', $this->driver->fresh()->fcm_token);
    }

    /** A dead handset clears its token instead of being retried forever. */
    public function test_a_rejected_token_is_cleared_from_the_user(): void
    {
        $fake = new FakeFcmTransport;
        $fake->rejects = ['device-abc'];
        $this->app->instance(FcmTransport::class, $fake);

        (new SendFcmMessage(new FcmMessage('device-abc', 'Title', 'Body'), $this->driver->id))
            ->handle($fake);

        $this->assertNull($this->driver->fresh()->fcm_token);
    }

    /** A rejection must not clear a token the user has since replaced. */
    public function test_a_rejection_does_not_clear_a_newer_token(): void
    {
        $fake = new FakeFcmTransport;
        $fake->rejects = ['device-abc'];
        $this->app->instance(FcmTransport::class, $fake);

        $this->driver->update(['fcm_token' => 'device-new']);

        (new SendFcmMessage(new FcmMessage('device-abc', 'Title', 'Body'), $this->driver->id))
            ->handle($fake);

        $this->assertSame('device-new', $this->driver->fresh()->fcm_token);
    }
}
