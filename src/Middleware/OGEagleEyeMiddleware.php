<?php

namespace OGEagleEye\Laravel\Middleware;

use Closure;
use Illuminate\Http\Request;
use OGEagleEye\Laravel\OGEagleEyeServiceProvider;
use OGEagleEye\Laravel\Support\RequestContext;
use OGEagleEye\Monitor\OGEagleEye;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class OGEagleEyeMiddleware
{
    public function __construct(
        protected RequestContext $requestContext
    ) {}

    /**
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $started = microtime(true);

        /** @var Response $response */
        $response = $next($request);

        $request->attributes->set('ogeagleeye_duration_ms', (microtime(true) - $started) * 1000);

        return $response;
    }

    public function terminate(Request $request, Response $response): void
    {
        if (! config('ogeagleeye.enabled', true) || OGEagleEye::getClient() === null) {
            return;
        }

        try {
            $durationMs = (float) $request->attributes->get('ogeagleeye_duration_ms', 0);
            $status = (int) $response->getStatusCode();
            $slowThreshold = (int) config('ogeagleeye.slow_threshold_ms', 2000);
            $capture4xx = (bool) config('ogeagleeye.capture_4xx', false);

            $isServerError = $status >= 500;
            $isClientError = $capture4xx && $status >= 400 && $status < 500;
            $isSlow = $slowThreshold > 0 && $durationMs >= $slowThreshold;

            if ($isServerError || $isClientError || $isSlow) {
                $context = $this->requestContext->toArray($request);
                if ($isSlow && ! $isServerError && ! $isClientError) {
                    $context['slow'] = true;
                }

                OGEagleEye::captureHttpFailure([
                    'url' => '/'.$request->path(),
                    'method' => $request->method(),
                    'status_code' => $status,
                    'duration_ms' => (int) round($durationMs),
                    'ip' => (string) $request->ip(),
                ], $context);
            }
        } catch (Throwable) {
            // never break the host
        }

        try {
            OGEagleEyeServiceProvider::flushBuffered();
        } catch (Throwable) {
            // never break the host
        }
    }
}
