# ogeagleeye/monitor-laravel

Laravel SDK for OGEagleEye (Laravel 9–13). Ask your **system admin** for:

- **Ingest key** (`oge_…`)
- **Events endpoint** (e.g. `https://your-host/api/v1/events`)

## Requirements

- PHP 8.0+
- Laravel 9–13

## Install

```bash
composer require ogeagleeye/monitor-laravel
php artisan vendor:publish --tag=OGEagleEye-config
```

`ogeagleeye/monitor-php` is installed automatically as a dependency.

## Setup

1. Get the **ingest key** and **endpoint** from your system admin.
2. Add to `.env`:

```env
OGEAGLEEYE_KEY=oge_YOUR_KEY_FROM_ADMIN
OGEAGLEEYE_ENDPOINT=https://your-host/api/v1/events
OGEAGLEEYE_ENABLED=true
```

Optional:

```env
OGEAGLEEYE_ENVIRONMENT=production
OGEAGLEEYE_RELEASE=1.0.0
```

The service provider is auto-discovered. After env is set, exceptions, HTTP failures, and Laravel `Log::info` / `Log::error` (and other levels at or above `OGEAGLEEYE_LOG_LEVEL`, default `info`) are reported automatically — no extra code required.

Optional log controls:

```env
OGEAGLEEYE_CAPTURE_LOGS=true
OGEAGLEEYE_LOG_LEVEL=info
```

## Heartbeat

Heartbeats are **opt-in**. Error/event reporting does **not** send them. Until this app sends heartbeats, the OGEagleEye panel shows **Heartbeat: none**.

Enable auto-scheduling in `.env`:

```env
OGEAGLEEYE_HEARTBEAT_AUTO=true
# optional: OGEAGLEEYE_HEARTBEAT_CRON=* * * * *
```

Ensure **this** app’s Laravel scheduler cron is running:

```bash
* * * * * cd /path/to/this-app && php artisan schedule:run >> /dev/null 2>&1
```

Or send one manually:

```bash
php artisan ogeagleeye:heartbeat
```

## Scanning

Scans run **on this app’s server** (not on the OGEagleEye platform). The SDK checks local files, then POSTs a `scan_result` to your endpoint. The platform stores Scan reports and can alert on critical findings.

This is an integrity + PHP heuristics helper — **not** an antivirus.

```bash
php artisan ogeagleeye:scan
php artisan ogeagleeye:scan app public --reset-baseline
php artisan ogeagleeye:scan --no-heuristics
```

Schedule on **this** project’s host (in `routes/console.php`):

```php
$schedule->command('ogeagleeye:scan')->dailyAt('02:15');
```

Ensure Laravel’s scheduler cron is installed so the command actually runs. More detail: [docs/scanning.md](docs/scanning.md).
