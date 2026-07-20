# SDK Integration — Laravel

Install `ogeagleeye/monitor-laravel` in any Laravel **9–13** app (PHP **8.0+**). It wraps `ogeagleeye/monitor-php` and reports as SDK `monitor-laravel`.

## Install

```bash
composer require ogeagleeye/monitor-laravel
php artisan vendor:publish --tag=OGEagleEye-config
```

## Env

```env
OGEAGLEEYE_KEY=oge_your_ingest_key
OGEAGLEEYE_ENDPOINT=https://your-host/api/v1/events
OGEAGLEEYE_ENVIRONMENT="${APP_ENV}"
OGEAGLEEYE_RELEASE="${APP_VERSION}"
# Optional:
# OGEAGLEEYE_SLOW_THRESHOLD_MS=2000
# OGEAGLEEYE_CAPTURE_4XX=false
# OGEAGLEEYE_QUEUE=false
# OGEAGLEEYE_HEARTBEAT_AUTO=false
```

Auto-discovery registers `OGEagleEyeServiceProvider`. With `OGEAGLEEYE_KEY` + `OGEAGLEEYE_ENDPOINT` set, the SDK:

1. Captures exceptions via the app `ExceptionHandler` `reportable` callback (respects Laravel `dontReport` and `config('ogeagleeye.ignore_exceptions')`).
2. Registers global `OGEagleEyeMiddleware` — reports `http_failure` for status **≥ 500** (4xx opt-in), and requests slower than `slow_threshold_ms`.
3. Flushes on middleware `terminate()` / app terminating (never before the response). Optional queued flush via `OGEAGLEEYE_QUEUE=true` when the default queue is not `sync`.

## Heartbeat

```bash
php artisan ogeagleeye:heartbeat
```

Schedule it yourself, or set `OGEAGLEEYE_HEARTBEAT_AUTO=true` to register a cron entry from config.

## Manual capture

```php
use OGEagleEye\Monitor\OGEagleEye;

OGEagleEye::captureException($e, ['order_id' => $id]);
OGEagleEye::captureMessage('Something noteworthy', 'warning');
OGEagleEye::flush();
```

Events conform to [event-schema-v1](event-schema-v1.md). See the package README and `examples/demo-laravel-app`.
