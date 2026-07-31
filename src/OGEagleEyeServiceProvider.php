<?php

namespace OGEagleEye\Laravel;

use Illuminate\Contracts\Debug\ExceptionHandler;
use Illuminate\Contracts\Http\Kernel as HttpKernel;
use Illuminate\Log\Events\MessageLogged;
use Illuminate\Support\ServiceProvider;
use OGEagleEye\Laravel\Console\HeartbeatCommand;
use OGEagleEye\Laravel\Console\ScanCommand;
use OGEagleEye\Laravel\Jobs\FlushOGEagleEyeJob;
use OGEagleEye\Laravel\Logging\LogCapture;
use OGEagleEye\Laravel\Middleware\OGEagleEyeMiddleware;
use OGEagleEye\Laravel\Support\RequestContext;
use OGEagleEye\Monitor\OGEagleEye;
use Throwable;

class OGEagleEyeServiceProvider extends ServiceProvider
{
    public const SDK_NAME = 'monitor-laravel';

    public const SDK_VERSION = '0.3.0';

    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/ogeagleeye.php', 'ogeagleeye');

        $this->app->singleton(RequestContext::class, fn () => new RequestContext);
    }

    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../config/ogeagleeye.php' => config_path('ogeagleeye.php'),
            ], 'ogeagleeye-config');

            $this->commands([
                HeartbeatCommand::class,
                ScanCommand::class,
            ]);
        }

        if (! config('ogeagleeye.enabled', true)) {
            return;
        }

        $key = (string) config('ogeagleeye.key', '');
        $endpoint = (string) config('ogeagleeye.endpoint', '');

        if ($key === '' || $endpoint === '') {
            return;
        }

        OGEagleEye::init([
            'key' => $key,
            'endpoint' => $endpoint,
            'environment' => (string) config('ogeagleeye.environment', 'production'),
            'release' => config('ogeagleeye.release'),
            'sample_rate' => (float) config('ogeagleeye.sample_rate', 1.0),
            'debug' => (bool) config('ogeagleeye.debug', false),
            'app_root' => base_path(),
            'register_handlers' => false,
            'sdk_name' => self::SDK_NAME,
            'sdk_version' => self::SDK_VERSION,
        ]);

        $this->registerExceptionCapture();
        $this->registerLogCapture();
        $this->registerMiddleware();
        $this->registerHeartbeatSchedule();

        $this->app->terminating(function (): void {
            try {
                self::flushBuffered();
            } catch (Throwable) {
                // never break the host
            }
        });
    }

    protected function registerExceptionCapture(): void
    {
        $this->callAfterResolving(ExceptionHandler::class, function (ExceptionHandler $handler): void {
            if (! method_exists($handler, 'reportable')) {
                return;
            }

            $handler->reportable(function (Throwable $e): void {
                if ($this->shouldIgnoreException($e)) {
                    return;
                }

                try {
                    /** @var RequestContext $context */
                    $context = $this->app->make(RequestContext::class);
                    OGEagleEye::captureException($e, $context->toArray());
                } catch (Throwable) {
                    // never break the host
                }
            });
        });
    }

    protected function registerLogCapture(): void
    {
        if (! config('ogeagleeye.capture_logs', true)) {
            return;
        }

        if (! class_exists(MessageLogged::class)) {
            return;
        }

        $this->app['events']->listen(MessageLogged::class, LogCapture::class);
    }

    protected function registerMiddleware(): void
    {
        if (! $this->app->bound(HttpKernel::class)) {
            return;
        }

        /** @var HttpKernel $kernel */
        $kernel = $this->app->make(HttpKernel::class);

        if (method_exists($kernel, 'pushMiddleware')) {
            $kernel->pushMiddleware(OGEagleEyeMiddleware::class);
        }
    }

    protected function registerHeartbeatSchedule(): void
    {
        if (! config('ogeagleeye.heartbeat.auto_schedule', false)) {
            return;
        }

        if (! $this->app->runningInConsole()) {
            return;
        }

        $this->app->booted(function (): void {
            if (! $this->app->bound('Illuminate\Console\Scheduling\Schedule')) {
                return;
            }

            /** @var \Illuminate\Console\Scheduling\Schedule $schedule */
            $schedule = $this->app->make('Illuminate\Console\Scheduling\Schedule');
            $cron = (string) config('ogeagleeye.heartbeat.cron', '* * * * *');
            $schedule->command('ogeagleeye:heartbeat')->cron($cron);
        });
    }

    protected function shouldIgnoreException(Throwable $e): bool
    {
        $ignore = config('ogeagleeye.ignore_exceptions', []);

        if (! is_array($ignore)) {
            return false;
        }

        foreach ($ignore as $class) {
            if (is_string($class) && $e instanceof $class) {
                return true;
            }
        }

        return false;
    }

    /**
     * Flush buffered events (sync or queued with drained payloads).
     */
    public static function flushBuffered(): void
    {
        if (! config('ogeagleeye.enabled', true)) {
            return;
        }

        $client = OGEagleEye::getClient();
        if ($client === null) {
            return;
        }

        $payloads = $client->drainBuffer();
        if ($payloads === []) {
            return;
        }

        if (config('ogeagleeye.queue', false) && self::queuesConfigured()) {
            $pending = FlushOGEagleEyeJob::dispatch($payloads);

            $connection = config('ogeagleeye.queue_connection');
            if (is_string($connection) && $connection !== '') {
                $pending->onConnection($connection);
            }

            $queue = config('ogeagleeye.queue_name');
            if (is_string($queue) && $queue !== '') {
                $pending->onQueue($queue);
            }

            return;
        }

        $client->sendPayloads($payloads);
    }

    protected static function queuesConfigured(): bool
    {
        $default = config('queue.default');

        return is_string($default) && $default !== '' && $default !== 'sync';
    }
}
