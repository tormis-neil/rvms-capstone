<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\NotificationResource;
use App\Models\Notification;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Notification inbox (FR-21) — both roles.
 *
 * A notification is addressed to ONE user, so every query here is filtered by
 * the authenticated user's id, not merely by agency. Agency scoping still
 * applies underneath (BelongsToAgency), but on its own it would let one admin
 * read a colleague's inbox — an agency may have several administrators.
 */
class NotificationController extends Controller
{
    public function index(Request $request)
    {
        $notifications = Notification::query()
            ->forUser($request->user()->id)
            ->when($request->boolean('unread'), fn ($q) => $q->unread())
            ->latest('created_at')
            ->latest('id')
            ->paginate(20);

        return NotificationResource::collection($notifications)
            ->additional(['meta' => [
                'unread_count' => Notification::query()
                    ->forUser($request->user()->id)->unread()->count(),
            ]]);
    }

    /**
     * PATCH /notifications/{id}/read — idempotent; re-reading keeps the
     * original read_at rather than moving it.
     */
    public function read(Request $request, Notification $notification): JsonResponse
    {
        $this->assertOwned($request, $notification);

        if (! $notification->is_read) {
            $notification->update(['is_read' => true, 'read_at' => now()]);
        }

        return NotificationResource::make($notification->fresh())
            ->response();
    }

    /** PATCH /notifications/read-all — clears the caller's own unread count. */
    public function readAll(Request $request): JsonResponse
    {
        $marked = Notification::query()
            ->forUser($request->user()->id)
            ->unread()
            ->update(['is_read' => true, 'read_at' => now()]);

        return response()->json(['data' => ['marked_read' => $marked, 'unread_count' => 0]]);
    }

    /**
     * Someone else's notification is not found, not forbidden: a 403 would
     * confirm the row exists.
     */
    private function assertOwned(Request $request, Notification $notification): void
    {
        abort_if($notification->user_id !== $request->user()->id, 404);
    }
}
