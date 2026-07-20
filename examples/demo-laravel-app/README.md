# Demo Laravel app (OGEagleEye SDK)

Throwaway Laravel 13 app for dogfooding `ogeagleeye/monitor-laravel` against a local platform.

## Setup

```bash
cd examples/demo-laravel-app
composer install
cp .env.example .env   # if needed
php artisan key:generate
```

Set ingest credentials (from a Laravel project in the panel):

```env
OGEAGLEEYE_KEY=oge_...
OGEAGLEEYE_ENDPOINT=http://platform.test/api/v1/events
OGEAGLEEYE_SLOW_THRESHOLD_MS=2000
```

## Run

```bash
php artisan serve --port=8081
# or link with Herd
```

## E2E checks

```bash
curl -s http://127.0.0.1:8081/boom      # → error issue (route demo.boom)
curl -s http://127.0.0.1:8081/slow      # → http_failure, duration_ms ≥ 3000
```

Process the platform `ingest` queue if not using sync:

```bash
cd ../../../ogeagleeye-platform
php artisan queue:work redis --queue=ingest --once
```
