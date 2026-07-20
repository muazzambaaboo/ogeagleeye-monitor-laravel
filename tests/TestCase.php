<?php

namespace OGEagleEye\Laravel\Tests;

use Orchestra\Testbench\TestCase as Orchestra;
use OGEagleEye\Laravel\OGEagleEyeServiceProvider;
use OGEagleEye\Monitor\OGEagleEye;

abstract class TestCase extends Orchestra
{
    protected ?RecordingTransport $transport = null;

    protected function setUp(): void
    {
        parent::setUp();

        $this->transport = new RecordingTransport;
        OGEagleEye::reset();
    }

    protected function tearDown(): void
    {
        OGEagleEye::reset();
        parent::tearDown();
    }

    protected function getPackageProviders($app): array
    {
        return [
            OGEagleEyeServiceProvider::class,
        ];
    }

    protected function defineEnvironment($app): void
    {
        $app['config']->set('ogeagleeye.enabled', true);
        $app['config']->set('ogeagleeye.key', 'oge_test_key_laravel_sdk');
        $app['config']->set('ogeagleeye.endpoint', 'http://ogeagleeye.test/api/v1/events');
        $app['config']->set('ogeagleeye.environment', 'testing');
        $app['config']->set('ogeagleeye.release', 'test-1.0.0');
        $app['config']->set('ogeagleeye.sample_rate', 1.0);
        $app['config']->set('ogeagleeye.debug', false);
        $app['config']->set('ogeagleeye.queue', false);
        $app['config']->set('ogeagleeye.slow_threshold_ms', 50);
        $app['config']->set('ogeagleeye.capture_4xx', false);
        $app['config']->set('ogeagleeye.ignore_exceptions', []);
        $app['config']->set('queue.default', 'sync');
    }

    /**
     * Re-bind core SDK with a recording transport after the provider booted.
     */
    protected function useRecordingTransport(): RecordingTransport
    {
        $transport = new RecordingTransport;

        OGEagleEye::init([
            'key' => config('ogeagleeye.key'),
            'endpoint' => config('ogeagleeye.endpoint'),
            'environment' => config('ogeagleeye.environment'),
            'release' => config('ogeagleeye.release'),
            'sample_rate' => (float) config('ogeagleeye.sample_rate', 1.0),
            'register_handlers' => false,
            'sdk_name' => OGEagleEyeServiceProvider::SDK_NAME,
            'sdk_version' => OGEagleEyeServiceProvider::SDK_VERSION,
            'transport' => $transport,
            'app_root' => base_path(),
        ]);

        $this->transport = $transport;

        return $transport;
    }

    protected function defineRoutes($router): void
    {
        $router->get('/boom', function () {
            throw new \RuntimeException('demo boom');
        })->name('demo.boom');

        $router->get('/slow', function () {
            usleep(80_000);

            return response('slow-ok', 200);
        })->name('demo.slow');

        $router->get('/ok', function () {
            return response('ok', 200);
        })->name('demo.ok');

        $router->get('/fail', function () {
            return response('fail', 500);
        })->name('demo.fail');
    }
}
