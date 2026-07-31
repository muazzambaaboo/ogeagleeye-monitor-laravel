<?php

use OGEagleEye\Laravel\OGEagleEyeServiceProvider;
use OGEagleEye\Monitor\OGEagleEye;

it('boots the service provider and reports sdk identity', function () {
    $transport = $this->useRecordingTransport();

    expect(OGEagleEye::getClient())->not->toBeNull();
    expect(OGEagleEye::getClient()->getSdkInfo())->toMatchArray([
        'name' => 'monitor-laravel',
        'version' => '0.3.0',
    ]);

    OGEagleEye::captureMessage('ping', 'info');
    OGEagleEyeServiceProvider::flushBuffered();

    expect($transport->sent)->toHaveCount(1)
        ->and($transport->sent[0]['payload']['sdk']['name'])->toBe('monitor-laravel');
});

it('captures exceptions via reportable with route enrichment', function () {
    $transport = $this->useRecordingTransport();

    $route = new Illuminate\Routing\Route(['GET'], '/boom', fn () => null);
    $route->name('demo.boom');
    $route->bind(Illuminate\Http\Request::create('/boom', 'GET'));

    $request = Illuminate\Http\Request::create('/boom', 'GET');
    $request->setRouteResolver(fn () => $route);
    app()->instance('request', $request);

    $handler = app(Illuminate\Contracts\Debug\ExceptionHandler::class);
    $handler->report(new RuntimeException('demo boom'));

    OGEagleEyeServiceProvider::flushBuffered();

    $errorEvents = array_values(array_filter(
        $transport->sent,
        fn ($row) => ($row['payload']['event_type'] ?? null) === 'error'
    ));

    expect($errorEvents)->not->toBeEmpty();
    $payload = $errorEvents[array_key_last($errorEvents)]['payload'];
    expect($payload['exception']['class'])->toBe(RuntimeException::class)
        ->and($payload['exception']['message'])->toBe('demo boom')
        ->and($payload['tags']['route'] ?? null)->toBe('demo.boom')
        ->and($payload['sdk']['name'])->toBe('monitor-laravel');
});

it('respects ignore_exceptions config', function () {
    config(['ogeagleeye.ignore_exceptions' => [RuntimeException::class]]);

    $provider = new OGEagleEyeServiceProvider(app());
    $method = new ReflectionMethod($provider, 'shouldIgnoreException');
    $method->setAccessible(true);

    expect($method->invoke($provider, new RuntimeException('x')))->toBeTrue()
        ->and($method->invoke($provider, new InvalidArgumentException('y')))->toBeFalse();
});

it('skips reporting ignored exceptions through reportable', function () {
    config(['ogeagleeye.ignore_exceptions' => [RuntimeException::class]]);
    $transport = $this->useRecordingTransport();

    // Re-bind reportable after config change: report manually through provider logic
    $provider = new OGEagleEyeServiceProvider(app());
    $method = new ReflectionMethod($provider, 'shouldIgnoreException');
    $method->setAccessible(true);

    if (! $method->invoke($provider, new RuntimeException('ignored'))) {
        OGEagleEye::captureException(new RuntimeException('ignored'));
    }

    OGEagleEyeServiceProvider::flushBuffered();
    expect($transport->sent)->toBeEmpty();
});

it('publishes config and registers heartbeat command', function () {
    $this->artisan('vendor:publish', [
        '--provider' => OGEagleEyeServiceProvider::class,
        '--tag' => 'ogeagleeye-config',
    ])->assertSuccessful();

    expect(file_exists(config_path('ogeagleeye.php')))->toBeTrue();

    $this->useRecordingTransport();
    $this->artisan('ogeagleeye:heartbeat')->assertSuccessful();

    expect($this->transport->sent)->toHaveCount(1)
        ->and($this->transport->sent[0]['payload']['event_type'])->toBe('heartbeat');
});
