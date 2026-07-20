<?php

namespace OGEagleEye\Laravel\Tests;

use OGEagleEye\Monitor\TransportInterface;

class RecordingTransport implements TransportInterface
{
    /** @var list<array{endpoint: string, key: string, payload: array<string, mixed>}> */
    public array $sent = [];

    /**
     * @param string               $endpoint
     * @param string               $ingestKey
     * @param array<string, mixed> $payload
     *
     * @return void
     */
    public function send($endpoint, $ingestKey, array $payload)
    {
        $this->sent[] = [
            'endpoint' => $endpoint,
            'key' => $ingestKey,
            'payload' => $payload,
        ];
    }
}
