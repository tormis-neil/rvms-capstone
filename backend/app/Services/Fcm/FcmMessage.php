<?php

namespace App\Services\Fcm;

/**
 * One push destined for one device (FR-21).
 *
 * `data` values must be strings — FCM's HTTP v1 API rejects a data payload
 * with non-string values, which is an easy way to get a 400 at runtime that
 * never shows up in a unit test.
 */
class FcmMessage
{
    public function __construct(
        public readonly string $token,
        public readonly string $title,
        public readonly string $body,
        public readonly array $data = [],
    ) {}

    /** The exact body FCM HTTP v1 expects. */
    public function toPayload(): array
    {
        $data = array_map(static fn ($value) => (string) $value, $this->data);

        return [
            'message' => [
                'token' => $this->token,
                'notification' => [
                    'title' => $this->title,
                    'body' => $this->body,
                ],
                // An empty PHP array encodes to `[]`, and FCM's parser rejects
                // a JSON array where it expects a map — a 400 on a message
                // that carries no data at all. Cast so it encodes to `{}`.
                'data' => $data === [] ? (object) [] : $data,
                'android' => [
                    'priority' => 'high',
                    'notification' => ['channel_id' => 'rvms_alerts'],
                ],
            ],
        ];
    }
}
