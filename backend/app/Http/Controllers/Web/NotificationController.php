<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Notification;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Notifications dashboard page (FR-21) — the Blade twin of the notification
 * inbox API.
 *
 * A notification is addressed to ONE user, so every query filters on the
 * caller's id rather than only on agency: an agency may have several
 * administrators and one must never read another's inbox.
 */
class NotificationController extends Controller
{
    public function index(Request $request): View
    {
        $notifications = Notification::query()
            ->forUser($request->user()->id)
            ->latest('created_at')
            ->latest('id')
            ->get();

        // The prototype groups the page under Today / Yesterday / Earlier and
        // renders nothing for an empty group (agency.js renderNotificationsPage).
        $groups = $notifications->groupBy(fn (Notification $n) => $n->group());

        return view('notifications', [
            'groups' => $groups,
            'unreadCount' => $notifications->where('is_read', false)->count(),
            // Drives the Clear Read button's disabled state and the count the
            // confirmation names, so the admin knows what they are removing.
            'readCount' => $notifications->where('is_read', true)->count(),
        ]);
    }

    /**
     * Opening a notification marks it read and takes the admin to the module it
     * concerns — the prototype's rows are links, so a click has to do both.
     */
    public function open(Request $request, Notification $notification): RedirectResponse
    {
        abort_if($notification->user_id !== $request->user()->id, 404);

        if (! $notification->is_read) {
            $notification->update(['is_read' => true, 'read_at' => now()]);
        }

        return redirect()->to($notification->link());
    }

    public function readAll(Request $request): RedirectResponse
    {
        Notification::query()
            ->forUser($request->user()->id)
            ->unread()
            ->update(['is_read' => true, 'read_at' => now()]);

        return back(fallback: route('notifications'))
            ->with('status', 'All notifications marked as read.');
    }

    /**
     * Clear the admin's READ notifications (project-lead approved 2026-07).
     *
     * Read-only by design: an alert nobody has opened yet cannot be destroyed
     * by a stray click, so the FR-21 delivery guarantee still holds for
     * anything the admin has not actually seen. Scoped to the caller's own id
     * for the same reason `readAll` is — an agency may have several
     * administrators, and one clearing their inbox must not empty another's.
     */
    public function clearRead(Request $request): RedirectResponse
    {
        $cleared = Notification::query()
            ->forUser($request->user()->id)
            ->where('is_read', true)
            ->delete();

        return back(fallback: route('notifications'))->with(
            'status',
            $cleared === 0
                ? 'There were no read notifications to clear.'
                : trans_choice('Cleared :count read notification.|Cleared :count read notifications.', $cleared, ['count' => $cleared]),
        );
    }
}
