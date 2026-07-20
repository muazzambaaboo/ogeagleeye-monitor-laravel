# ogeagleeye/monitor-laravel

Laravel SDK for OGEagleEye. Wraps [`ogeagleeye/monitor-php`](../ogeagleeye-monitor-php) (sibling local repo). Targets **Laravel 9–13** / **PHP 8.0+**.

Version: see [VERSION](VERSION) / [CHANGELOG.md](CHANGELOG.md).

## Install

```bash
composer require ogeagleeye/monitor-laravel
php artisan vendor:publish --tag=OGEagleEye-config
```

### Local path dependency (this repo)

`composer.json` includes a **path repository** to `../ogeagleeye-monitor-php` so `composer install` resolves `ogeagleeye/monitor-php` from the sibling folder. **Remove that `repositories` entry** (and depend on Packagist) once packages are published.

Consumers developing against both SDKs locally should also add:

```json
"repositories": [
  { "type": "path", "url": "../ogeagleeye-monitor-php", "options": { "symlink": true } },
  { "type": "path", "url": "../ogeagleeye-monitor-laravel", "options": { "symlink": true } }
]
```

Set:

```env
OGEAGLEEYE_KEY=oge_...
OGEAGLEEYE_ENDPOINT=https://your-host/api/v1/events
```

## What it captures

| Source | Behavior |
|--------|----------|
| Exceptions | `ExceptionHandler::reportable` (L9–13). Honors `dontReport` + `ignore_exceptions`. |
| HTTP ≥ 500 | Always (`http_failure`) |
| HTTP 4xx | When `capture_4xx=true` |
| Slow requests | When duration ≥ `slow_threshold_ms` (default 2000) |
| Heartbeat | `php artisan ogeagleeye:heartbeat` |
| File scan | `php artisan ogeagleeye:scan` → `scan_result` (integrity + PHP heuristics; **not an AV**) |

Enrichment: route name, auth user id/email (hashed), release tag.

Flush runs in middleware `terminate()` / app terminating. With `queue=true` and a non-`sync` queue driver, payloads are dispatched via `FlushOGEagleEyeJob`.

## Docs

- [docs/quickstart-laravel.md](docs/quickstart-laravel.md)
- [docs/sdk-integration-laravel.md](docs/sdk-integration-laravel.md)
- [docs/scanning.md](docs/scanning.md)
- [docs/event-schema-v1.md](docs/event-schema-v1.md)

## Tests

```bash
composer install
./vendor/bin/pest
```

## Demo

See [`examples/demo-laravel-app`](examples/demo-laravel-app).
