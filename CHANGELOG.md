# Changelog

## 0.3.0

- Capture Laravel `Log::` / Monolog writes (info, error, and levels at or above `OGEAGLEEYE_LOG_LEVEL`) as `event_type=log`.
- Config: `capture_logs` / `OGEAGLEEYE_CAPTURE_LOGS`, `log_level` / `OGEAGLEEYE_LOG_LEVEL` (default `info`).

## 0.1.0

- Initial standalone repository split from the OGEagleEye monorepo.
- Laravel SDK (`ogeagleeye/monitor-laravel`) wrapping `ogeagleeye/monitor-php`.
