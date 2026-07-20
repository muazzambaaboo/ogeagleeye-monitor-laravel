<?php

namespace OGEagleEye\Laravel\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use OGEagleEye\Monitor\OGEagleEye;
use Throwable;

class FlushOGEagleEyeJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;

    /**
     * @param  list<array<string, mixed>>  $payloads
     */
    public function __construct(
        public array $payloads
    ) {}

    public function handle(): void
    {
        try {
            $client = OGEagleEye::getClient();
            if ($client === null) {
                return;
            }

            $client->sendPayloads($this->payloads);
        } catch (Throwable) {
            // swallow — monitoring must never fail the host queue
        }
    }
}
