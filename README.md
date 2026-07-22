# ogeagleeye/monitor-laravel

Laravel SDK for OGEagleEye (Laravel 9–13). Ask your **system admin** for:

- **Ingest key** (`oge_…`)
- **Events endpoint** (e.g. `https://your-host/api/v1/events`)

## Requirements

- PHP 8.0+
- Laravel 9–13

## Install

Both this package and the core PHP SDK are private. Register both repos, then require:

```bash
composer config repositories.ogeagleeye-monitor-php vcs https://github.com/muazzambaaboo/ogeagleeye-monitor-php.git
composer config repositories.ogeagleeye-monitor-laravel vcs https://github.com/muazzambaaboo/ogeagleeye-monitor-laravel.git
composer require ogeagleeye/monitor-php:dev-master ogeagleeye/monitor-laravel:dev-master
php artisan vendor:publish --tag=OGEagleEye-config
```

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
