<?php

namespace App\Services\Fcm;

/**
 * Test double: records every push instead of sending it, so a test can assert
 * WHO was pushed and WHAT they were told without any network.
 */
class FakeFcmTransport implements FcmTransport
{
    /** @var array<int, FcmMessage> */
    public array $sent = [];

    /** Tokens that should report non-delivery, e.g. a stale handset. */
    public array $rejects = [];

    public function send(FcmMessage $message): bool
    {
        $this->sent[] = $message;

        return ! in_array($message->token, $this->rejects, true);
    }

    /** @return array<int, FcmMessage> */
    public function sentTo(string $token): array
    {
        return array_values(array_filter($this->sent, fn (FcmMessage $m) => $m->token === $token));
    }

    public function count(): int
    {
        return count($this->sent);
    }
}
