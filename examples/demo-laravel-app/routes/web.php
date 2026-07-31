<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return response()->json([
        'app' => 'demo-laravel-app',
        'routes' => [
            'GET /boom' => 'throws RuntimeException (error event)',
            'GET /slow' => 'sleeps 3s then 200 (http_failure if slow_threshold_ms ≤ 3000)',
            'GET /fail' => 'returns 500 (http_failure)',
            'GET /log-info' => 'Log::info → log event',
            'GET /log-error' => 'Log::error → log event',
        ],
    ]);
});

Route::get('/boom', function () {
    throw new RuntimeException('Demo boom from examples/demo-laravel-app');
})->name('demo.boom');

Route::get('/slow', function () {
    sleep(3);

    return response('slow-ok', 200);
})->name('demo.slow');

Route::get('/fail', function () {
    return response('fail', 500);
})->name('demo.fail');

Route::get('/log-info', function () {
    \Illuminate\Support\Facades\Log::info('Demo Log::info from examples/demo-laravel-app');

    return response('logged-info', 200);
})->name('demo.log-info');

Route::get('/log-error', function () {
    \Illuminate\Support\Facades\Log::error('Demo Log::error from examples/demo-laravel-app');

    return response('logged-error', 200);
})->name('demo.log-error');
