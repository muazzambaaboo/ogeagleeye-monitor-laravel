<?php

use Illuminate\Support\Facades\Queue;
use OGEagleEye\Laravel\Jobs\FlushOGEagleEyeJob;
use OGEagleEye\Laravel\OGEagleEyeServiceProvider;
use OGEagleEye\Laravel\Support\RequestContext;
use OGEagleEye\Monitor\OGEagleEye;

it('captures http_failure for 500 responses', function () {
    $transport = $this->useRecordingTransport();

    $this->get('/fail');

    $httpEvents = array_values(array_filter(
        $transport->sent,
        fn ($row) => ($row['payload']['event_type'] ?? null) === 'http_failure'
    ));

    expect($httpEvents)->toHaveCount(1);
    $payload = $httpEvents[0]['payload'];
    expect($payload['request']['status_code'])->toBe(500)
        ->and($payload['request']['method'])->toBe('GET')
        ->and($payload['tags']['route'] ?? null)->toBe('demo.fail')
        ->and($payload['sdk']['name'])->toBe('monitor-laravel');
});

it('captures slow requests as http_failure with duration_ms', function () {
    config(['ogeagleeye.slow_threshold_ms' => 50]);
    $transport = $this->useRecordingTransport();

    $this->get('/slow');

    $httpEvents = array_values(array_filter(
        $transport->sent,
        fn ($row) => ($row['payload']['event_type'] ?? null) === 'http_failure'
    ));

    expect($httpEvents)->toHaveCount(1);
    $payload = $httpEvents[0]['payload'];
    expect($payload['request']['duration_ms'])->toBeGreaterThanOrEqual(50)
        ->and($payload['request']['status_code'])->toBe(200)
        ->and($payload['context']['slow'] ?? false)->toBeTrue()
        ->and($payload['tags']['route'] ?? null)->toBe('demo.slow');
});

it('does not capture fast 200 responses', function () {
    config(['ogeagleeye.slow_threshold_ms' => 5000]);
    $transport = $this->useRecordingTransport();

    $this->get('/ok');

    expect($transport->sent)->toBeEmpty();
});

it('dispatches flush job when queue mode is enabled', function () {
    Queue::fake();
    config([
        'ogeagleeye.queue' => true,
        'queue.default' => 'redis',
    ]);

    $this->useRecordingTransport();

    OGEagleEye::captureMessage('queued', 'info');
    OGEagleEyeServiceProvider::flushBuffered();

    Queue::assertPushed(FlushOGEagleEyeJob::class, function (FlushOGEagleEyeJob $job) {
        return count($job->payloads) === 1
            && ($job->payloads[0]['event_type'] ?? null) === 'log';
    });
});

it('enriches events with auth user id', function () {
    $user = new class implements Illuminate\Contracts\Auth\Authenticatable
    {
        public int $id = 42;

        public string $email = 'user@example.com';

        public function getAuthIdentifierName(): string
        {
            return 'id';
        }

        public function getAuthIdentifier(): mixed
        {
            return $this->id;
        }

        public function getAuthPasswordName(): string
        {
            return 'password';
        }

        public function getAuthPassword(): string
        {
            return '';
        }

        public function getRememberToken(): ?string
        {
            return null;
        }

        public function setRememberToken($value): void {}

        public function getRememberTokenName(): string
        {
            return '';
        }
    };

    $this->actingAs($user);

    $context = app(RequestContext::class)->toArray(request());

    expect($context['user']['id'] ?? null)->toBe('42');
});
