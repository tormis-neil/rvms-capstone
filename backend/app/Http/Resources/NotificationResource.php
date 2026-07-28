<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin \App\Models\Notification
 */
class NotificationResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'type' => $this->type,
            'title' => $this->title,
            'message' => $this->message,
            // Exposed as `payload`, not `data`: a resource array containing a
            // `data` key makes Laravel skip its own `data` envelope, so single
            // resources came back unwrapped while collections stayed wrapped —
            // two shapes for the mobile app to parse. The column is still
            // `data` (per the data dictionary); only the JSON key differs.
            'payload' => $this->data,
            'is_read' => $this->is_read,
            'read_at' => $this->read_at,
            'created_at' => $this->created_at,
            // Presentation the mobile app and the bell both render from
            // (the prototype's NOTIF map, kept server-side so both platforms agree).
            'icon' => $this->icon(),
            'tone' => $this->tone(),
            'group' => $this->group(),
            'time_label' => $this->timeLabel(),
        ];
    }
}
