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
}
