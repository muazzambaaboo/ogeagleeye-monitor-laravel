<?php

use Illuminate\Support\Facades\Log;
use OGEagleEye\Laravel\OGEagleEyeServiceProvider;

it('captures Log::info and Log::error as log events', function () {
    $transport = $this->useRecordingTransport();

    Log::info('hello from info', ['order_id' => 42]);
    Log::error('hello from error');

    OGEagleEyeServiceProvider::flushBuffered();

    $logs = array_values(array_filter(
        $transport->sent,
        fn ($row) => ($row['payload']['event_type'] ?? null) === 'log'
    ));

    expect($logs)->toHaveCount(2);

    $info = $logs[0]['payload'];
    expect($info['context']['message'])->toBe('hello from info')
        ->and($info['context']['level'])->toBe('info')
        ->and($info['context']['order_id'])->toBe(42)
        ->and($info['sdk']['name'])->toBe('monitor-laravel');

    $error = $logs[1]['payload'];
    expect($error['context']['message'])->toBe('hello from error')
        ->and($error['context']['level'])->toBe('error');
});

it('respects OGEAGLEEYE_LOG_LEVEL minimum', function () {
    config(['ogeagleeye.log_level' => 'error']);
    $transport = $this->useRecordingTransport();

    Log::info('should be filtered');
    Log::warning('also filtered');
    Log::error('should ship');

    OGEagleEyeServiceProvider::flushBuffered();

    $logs = array_values(array_filter(
        $transport->sent,
        fn ($row) => ($row['payload']['event_type'] ?? null) === 'log'
    ));

    expect($logs)->toHaveCount(1)
        ->and($logs[0]['payload']['context']['message'])->toBe('should ship')
        ->and($logs[0]['payload']['context']['level'])->toBe('error');
});

it('skips log capture when capture_logs is false', function () {
    config(['ogeagleeye.capture_logs' => false]);
    $transport = $this->useRecordingTransport();

    // Re-boot is heavy; invoke listener config gate by dispatching through LogCapture
    $capture = app(\OGEagleEye\Laravel\Logging\LogCapture::class);
    $capture->handle(new Illuminate\Log\Events\MessageLogged('error', 'muted', []));

    OGEagleEyeServiceProvider::flushBuffered();

    expect($transport->sent)->toBeEmpty();
});

it('does not capture debug below default info threshold', function () {
    $transport = $this->useRecordingTransport();

    Log::debug('noisy debug');

    OGEagleEyeServiceProvider::flushBuffered();

    expect($transport->sent)->toBeEmpty();
});
