<?php

namespace App\Services\Fcm;

/**
 * One push destined for one device (FR-21).
 *
 * `data` values must be strings — FCM's HTTP v1 API rejects a data payload
 * with non-string values, which is an easy way to get a 400 at runtime that
 * never shows up in a unit test.
 *
 * `data` KEYS are the other trap, and the one that actually bit us: FCM keeps
 * a set of reserved words for its own protocol, and a message using one is
 * rejected whole. The vehicle-status alert shipped with `from` in its payload,
 * so it stored its notification row, queued its push, and was refused by
 * Google every time — while every other FR-21 alert delivered normally.
 */
class FcmMessage
{
    /**
     * Keys FCM keeps for itself. Anything starting with `google` or `gcm` is
     * reserved too; the rest are its own protocol fields.
     *
     * @var list<string>
     */
    private const RESERVED_KEYS = [
        'from',
        'to',
        'notification',
        'message_type',
        'collapse_key',
        'priority',
        'content_available',
        'mutable_content',
        'time_to_live',
        'delay_while_idle',
        'restricted_package_name',
        'registration_ids',
        'dry_run',
    ];

    public function __construct(
        public readonly string $token,
        public readonly string $title,
        public readonly string $body,
        public readonly array $data = [],
    ) {}

    /** The exact body FCM HTTP v1 expects. */
    public function toPayload(): array
    {
        $this->assertNoReservedKeys();

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

    /**
     * Refuse a reserved key here rather than letting Google find it.
     *
     * FCM's answer is a 400 naming a JSON field, which reads as a malformed
     * request rather than "one key in your payload is a protocol word", and it
     * arrives only on the alert that happens to use one — so the system looks
     * intermittently broken instead of consistently wrong. Raised inside the
     * queued job, never in the request that triggered it, so a bad key can
     * never fail an admin's status change.
     *
     * @throws FcmConfigurationException
     */
    private function assertNoReservedKeys(): void
    {
        foreach (array_keys($this->data) as $key) {
            $lower = strtolower((string) $key);

            $reserved = in_array($lower, self::RESERVED_KEYS, true)
                || str_starts_with($lower, 'google')
                || str_starts_with($lower, 'gcm');

            if ($reserved) {
                throw new FcmConfigurationException(
                    "The push data payload uses \"{$key}\", which FCM reserves for its own protocol. "
                    .'Google rejects the whole message, so this alert would never reach a handset. '
                    .'Rename the key (e.g. "from" → "from_status") in whichever trigger built it.'
                );
            }
        }
    }
}
