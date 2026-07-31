<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Ingest credentials
    |--------------------------------------------------------------------------
    */
    'key' => env('OGEAGLEEYE_KEY'),

    'endpoint' => env('OGEAGLEEYE_ENDPOINT', 'http://platform.test/api/v1/events'),

    /*
    |--------------------------------------------------------------------------
    | Runtime
    |--------------------------------------------------------------------------
    */
    'environment' => env('OGEAGLEEYE_ENVIRONMENT', env('APP_ENV', 'production')),

    'release' => env('OGEAGLEEYE_RELEASE', env('APP_VERSION')),

    'sample_rate' => (float) env('OGEAGLEEYE_SAMPLE_RATE', 1.0),

    'debug' => (bool) env('OGEAGLEEYE_DEBUG', false),

    'enabled' => (bool) env('OGEAGLEEYE_ENABLED', true),

    /*
    |--------------------------------------------------------------------------
    | HTTP capture (OGEagleEyeMiddleware)
    |--------------------------------------------------------------------------
    */
    'slow_threshold_ms' => (int) env('OGEAGLEEYE_SLOW_THRESHOLD_MS', 2000),

    'capture_4xx' => (bool) env('OGEAGLEEYE_CAPTURE_4XX', false),

    /*
    |--------------------------------------------------------------------------
    | Laravel Log:: / Monolog capture → event_type=log
    |--------------------------------------------------------------------------
    |
    | When enabled, Log::info / Log::error (and other levels at or above
    | log_level) are forwarded to OGEagleEye. Set OGEAGLEEYE_CAPTURE_LOGS=false
    | to disable. Minimum level: debug|info|notice|warning|error|critical|alert|emergency
    |
    */
    'capture_logs' => (bool) env('OGEAGLEEYE_CAPTURE_LOGS', true),

    'log_level' => env('OGEAGLEEYE_LOG_LEVEL', 'info'),

    /*
    |--------------------------------------------------------------------------
    | Exception ignore list (FQCN strings). Laravel dontReport is also respected.
    |--------------------------------------------------------------------------
    */
    'ignore_exceptions' => [
        // \Illuminate\Auth\AuthenticationException::class,
    ],

    /*
    |--------------------------------------------------------------------------
    | Queue flush: when true and a queue connection is configured, flush runs
    | as a job after the response (terminate). Otherwise flush is synchronous.
    |--------------------------------------------------------------------------
    */
    'queue' => (bool) env('OGEAGLEEYE_QUEUE', false),

    'queue_connection' => env('OGEAGLEEYE_QUEUE_CONNECTION'),

    'queue_name' => env('OGEAGLEEYE_QUEUE_NAME', 'default'),

    /*
    |--------------------------------------------------------------------------
    | Heartbeat
    |--------------------------------------------------------------------------
    */
    'heartbeat' => [
        'auto_schedule' => (bool) env('OGEAGLEEYE_HEARTBEAT_AUTO', false),
        'cron' => env('OGEAGLEEYE_HEARTBEAT_CRON', '* * * * *'),
    ],

    /*
    |--------------------------------------------------------------------------
    | File integrity / heuristic scan (ogeagleeye:scan)
    |--------------------------------------------------------------------------
    |
    | This is NOT an antivirus. It reports integrity diffs + lightweight PHP
    | heuristics (eval(base64…), PHP in upload dirs, etc.). See docs.
    |
    */
    'scan' => [
        'root' => env('OGEAGLEEYE_SCAN_ROOT'), // null → base_path() at runtime
        'paths' => array_values(array_filter(array_map('trim', explode(',', (string) env('OGEAGLEEYE_SCAN_PATHS', 'app,public,bootstrap,config,routes'))))),
        'manifest_path' => env('OGEAGLEEYE_SCAN_MANIFEST'), // null → storage/app/.ogeagleeye-manifest.json
        'heuristics' => (bool) env('OGEAGLEEYE_SCAN_HEURISTICS', true),
        'core_mtime_window' => (int) env('OGEAGLEEYE_SCAN_CORE_MTIME_WINDOW', 604800),
        'exclude' => null, // null → Scanner defaults (vendor excluded, uploads included)
    ],

];
