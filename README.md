# ogeagleeye/monitor-laravel

Laravel SDK for [OGEagleEye](https://github.com/muazzambaaboo/ogeagleeye-platform). Wraps [`ogeagleeye/monitor-php`](https://github.com/muazzambaaboo/ogeagleeye-monitor-php).

Captures exceptions, HTTP failures, slow requests, heartbeats, and optional file scans into your OGEagleEye project.

| | |
|---|---|
| **Package** | `ogeagleeye/monitor-laravel` |
| **PHP** | ≥ 8.0 |
| **Laravel** | 9 – 13 |
| **Depends on** | `ogeagleeye/monitor-php` |
| **Platform** | [ogeagleeye-platform](https://github.com/muazzambaaboo/ogeagleeye-platform) |

Version: see [VERSION](VERSION) / [CHANGELOG.md](CHANGELOG.md).

---

## What it is

Laravel service provider + middleware + Artisan commands that:

| Source | Behavior |
|--------|----------|
| Exceptions | Via `ExceptionHandler::reportable` (honors `dontReport` / ignore list) |
| HTTP ≥ 500 | Always (`http_failure`) |
| HTTP 4xx | When `capture_4xx=true` |
| Slow requests | Duration ≥ `slow_threshold_ms` (default 2000) |
| Heartbeat | `php artisan ogeagleeye:heartbeat` |
| File scan | `php artisan ogeagleeye:scan` → `scan_result` (**not** antivirus) |

Enrichment includes route name and hashed auth user id/email. Flush runs on middleware `terminate()` / app terminating. Optional queued flush via `FlushOGEagleEyeJob`.

---

## Install

### Packagist (when published)

```bash
composer require ogeagleeye/monitor-laravel
php artisan vendor:publish --tag=OGEagleEye-config
```

### From GitHub (VCS) — both packages

```bash
composer config repositories.ogeagleeye-monitor-php vcs https://github.com/muazzambaaboo/ogeagleeye-monitor-php.git
composer config repositories.ogeagleeye-monitor-laravel vcs https://github.com/muazzambaaboo/ogeagleeye-monitor-laravel.git
composer require ogeagleeye/monitor-php:dev-master ogeagleeye/monitor-laravel:dev-master
php artisan vendor:publish --tag=OGEagleEye-config
```

### Local path (sibling clones)

This package’s `composer.json` may include a **path** repo to `../ogeagleeye-monitor-php` for local dogfood. For consumers:

```json
{
  "repositories": [
    { "type": "path", "url": "../ogeagleeye-monitor-php", "options": { "symlink": true } },
    { "type": "path", "url": "../ogeagleeye-monitor-laravel", "options": { "symlink": true } }
  ],
  "require": {
    "ogeagleeye/monitor-laravel": "@dev"
  }
}
```

Auto-discovery registers `OGEagleEye\Laravel\OGEagleEyeServiceProvider`.

---

## Setup

1. Create a project in the panel (platform = **Laravel**) → copy `oge_…`.
2. Add to `.env`:

```env
OGEAGLEEYE_KEY=oge_YOUR_KEY
OGEAGLEEYE_ENDPOINT=https://monitor.example.com/api/v1/events
OGEAGLEEYE_ENVIRONMENT=production
OGEAGLEEYE_RELEASE=1.0.0
OGEAGLEEYE_ENABLED=true
OGEAGLEEYE_SLOW_THRESHOLD_MS=2000
```

3. Publish config (optional overrides):

```bash
php artisan vendor:publish --tag=OGEagleEye-config
```

4. Schedule heartbeat (recommended):

```php
// routes/console.php or app/Console/Kernel.php
Schedule::command('ogeagleeye:heartbeat')->everyFiveMinutes();
```

---

## How to use

Most apps need **no extra code** after install + env — exceptions and HTTP middleware report automatically.

### Manual capture

```php
use OGEagleEye\Monitor\OGEagleEye;

OGEagleEye::captureException($e, ['order_id' => $id]);
OGEagleEye::captureMessage('Checkout started', 'info');
OGEagleEye::flush();
```

### Artisan

```bash
php artisan ogeagleeye:heartbeat
php artisan ogeagleeye:scan
```

### Verify

```php
// routes/web.php
Route::get('/boom', fn () => throw new RuntimeException('Laravel boom'));
```

Hit `/boom`, process the platform `ingest` queue, open **Issues** in the panel.

---

## Demo

See [`examples/demo-laravel-app`](examples/demo-laravel-app).

---

## Tests

```bash
composer install
./vendor/bin/pest
```

---

## Docs

- [docs/quickstart-laravel.md](docs/quickstart-laravel.md)
- [docs/sdk-integration-laravel.md](docs/sdk-integration-laravel.md)
- [docs/scanning.md](docs/scanning.md)
- [docs/event-schema-v1.md](docs/event-schema-v1.md)

---

## License

MIT — see [LICENSE](LICENSE) if present.
