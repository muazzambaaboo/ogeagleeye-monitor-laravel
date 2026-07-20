# Quickstart — Laravel SDK

Copy-paste path from an empty Laravel app. Target: **< 10 minutes**.

## 1. Project key

Panel → **Projects → Create** (platform = Laravel) → copy `oge_…`.

## 2. Install

```bash
composer require ogeagleeye/monitor-laravel
php artisan vendor:publish --tag=OGEagleEye-config
```

Path repos (local sibling folders / pre-Packagist):

```json
{
  "repositories": [
    { "type": "path", "url": "../ogeagleeye-monitor-php" },
    { "type": "path", "url": "../ogeagleeye-monitor-laravel" }
  ]
}
```

## 3. `.env`

```env
OGEAGLEEYE_KEY=oge_YOUR_KEY
OGEAGLEEYE_ENDPOINT=http://platform.test/api/v1/events
OGEAGLEEYE_ENVIRONMENT=local
```

## 4. Boom route

```php
// routes/web.php
Route::get('/boom', function () {
    throw new RuntimeException('Quickstart Laravel boom');
});
```

Visit `/boom`, then drain `ingest` and open Issues. Expect route context on the event tags when middleware is active.

## Slow request

```php
Route::get('/slow', function () {
    usleep(3_100_000);
    return 'ok';
});
```

With default `OGEAGLEEYE_SLOW_THRESHOLD_MS=2000`, this emits `http_failure` with `duration_ms ≥ 3000`.

See: [sdk-integration-laravel.md](sdk-integration-laravel.md).
