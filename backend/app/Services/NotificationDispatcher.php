<?php

namespace App\Services;

use App\Jobs\SendFcmMessage;
use App\Models\Notification;
use App\Models\User;
use App\Services\Fcm\FcmMessage;
use Illuminate\Support\Collection;

/**
 * The one place a notification is raised (FR-21).
 *
 * Every trigger — new damage report, vehicle status change, driver
 * self-registration, license alerts, PM due alerts — goes through here so that
 * a stored row and a push can never drift apart: the row is the record the bell
 * and the notifications page read, the push is best-effort delivery on top.
 *
 * Pushes are never sent inline. An admin reviewing a damage report must not wait
 * on Google, and a Firebase outage must not fail the review.
 *
 * They are dispatched **after the response** rather than onto the queue, so a
 * deployed RVMS needs no `queue:work` process running to notify anyone. That
 * matters at turnover: the agencies configure ONE scheduled task, and a system
 * whose pushes depend on someone remembering to keep a console window open is a
 * system that stops notifying the first time the machine reboots. The work still
 * happens off the request — Laravel runs it once the response has been sent, in
 * web and console contexts alike, so the scheduled licence and PM sweeps push
 * too.
 *
 * The trade this makes: an after-response job does not retry, so a transient
 * Google failure loses that one banner. Acceptable here, and only here, because
 * the stored row above is written synchronously and is what FR-21 actually
 * guarantees — the driver still sees the alert in their inbox. A dropped push
 * costs a buzz, never a record.
 */
class NotificationDispatcher
{
    /**
     * Raise one notification for one recipient.
     *
     * @param  array<string, mixed>  $data  reference payload (plate, record id…)
     */
    public function send(User $recipient, string $type, string $title, string $message, array $data = []): Notification
    {
        $notification = Notification::query()->create([
            // Explicit rather than auto-stamped: an alert belongs to the
            // RECIPIENT's agency, which is not always the acting user's — a
            // scheduled command runs with no authenticated user at all.
            'agency_id' => $recipient->agency_id,
            'user_id' => $recipient->id,
            'type' => $type,
            'title' => $title,
            'message' => $message,
            'data' => $data,
        ]);

        if (filled($recipient->fcm_token)) {
            SendFcmMessage::dispatch(
                new FcmMessage(
                    token: $recipient->fcm_token,
                    title: $title,
                    body: $message,
                    data: ['type' => $type, 'notification_id' => $notification->id] + $data,
                ),
                $recipient->id,
            )->afterResponse();
        }

        return $notification;
    }

    /**
     * Raise the same notification for several recipients.
     *
     * Used for every admin-facing alert: an agency may have more than one
     * administrator (BFP has two by design), and FR-21 addresses them all —
     * notifying only the first would silently drop the others.
     *
     * @param  Collection<int, User>|array<int, User>  $recipients
     * @return Collection<int, Notification>
     */
    public function sendToMany(iterable $recipients, string $type, string $title, string $message, array $data = []): Collection
    {
        return collect($recipients)->map(
            fn (User $recipient) => $this->send($recipient, $type, $title, $message, $data)
        );
    }

    /** Every administrator of an agency — the audience for FR-21's admin alerts. */
    public function adminsOf(int $agencyId): Collection
    {
        return User::query()
            ->where('agency_id', $agencyId)
            ->where('role', User::ROLE_ADMIN)
            ->where('status', User::STATUS_ACTIVE)
            ->get();
    }
}
