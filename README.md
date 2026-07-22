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

The service provider is auto-discovered. After env is set, exceptions and HTTP failures are reported automatically — no extra code required.
