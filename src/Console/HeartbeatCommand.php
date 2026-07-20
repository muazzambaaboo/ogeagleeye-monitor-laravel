<?php

namespace OGEagleEye\Laravel\Console;

use Illuminate\Console\Command;
use OGEagleEye\Monitor\OGEagleEye;

class HeartbeatCommand extends Command
{
    protected $signature = 'ogeagleeye:heartbeat';

    protected $description = 'Send a OGEagleEye heartbeat event';

    public function handle(): int
    {
        if (! config('ogeagleeye.enabled', true) || OGEagleEye::getClient() === null) {
            $this->warn('OGEagleEye is not configured (set OGEAGLEEYE_KEY and OGEAGLEEYE_ENDPOINT).');

            return self::FAILURE;
        }

        OGEagleEye::heartbeat();
        OGEagleEye::flush();

        $this->info('Heartbeat sent.');

        return self::SUCCESS;
    }
}
