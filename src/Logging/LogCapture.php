<?php

namespace OGEagleEye\Laravel\Logging;

use Illuminate\Log\Events\MessageLogged;
use OGEagleEye\Laravel\Support\RequestContext;
use OGEagleEye\Monitor\OGEagleEye;
use Stringable;
use Throwable;

/**
 * Forwards Laravel Log:: / Monolog writes to OGEagleEye as event_type=log.
 */
class LogCapture
{
    /** @var array<string, int> */
    private const LEVELS = [
        'debug' => 100,
        'info' => 200,
        'notice' => 250,
        'warning' => 300,
        'error' => 400,
        'critical' => 500,
        'alert' => 550,
        'emergency' => 600,
    ];

    private static bool $capturing = false;

    public function __construct(
        protected RequestContext $requestContext
    ) {}

    public function handle(MessageLogged $event): void
    {
        if (self::$capturing) {
            return;
        }

        if (! config('ogeagleeye.capture_logs', true)) {
            return;
        }

        self::$capturing = true;

        try {
            $level = $this->normalizeLevel($event->level);

            if (! $this->meetsMinimumLevel($level)) {
                return;
            }

            $message = $event->message;
            if ($message instanceof Stringable) {
                $message = (string) $message;
            } else {
                $message = is_scalar($message) || $message === null
                    ? (string) $message
                    : '';
            }

            if ($message === '' || str_starts_with($message, '[ogeagleeye')) {
                return;
            }

            $extra = is_array($event->context)
                ? $this->sanitizeContext($event->context)
                : [];

            $context = array_replace_recursive(
                $this->requestContext->toArray(),
                $extra
            );

            OGEagleEye::captureMessage($message, $level, $context);
        } catch (Throwable) {
            // never break the host
        } finally {
            self::$capturing = false;
        }
    }

    /**
     * @param mixed $level
     */
    protected function normalizeLevel($level): string
    {
        if (is_object($level)) {
            if (isset($level->name) && is_string($level->name)) {
                $byName = strtolower($level->name);
                if (isset(self::LEVELS[$byName])) {
                    return $byName;
                }
            }

            if (method_exists($level, 'getName')) {
                return strtolower((string) $level->getName());
            }

            if (isset($level->value)) {
                return $this->normalizeLevel($level->value);
            }
        }

        if (is_int($level)) {
            $map = array_flip(self::LEVELS);

            return $map[$level] ?? 'info';
        }

        return strtolower((string) $level);
    }

    protected function meetsMinimumLevel(string $level): bool
    {
        $minimum = strtolower((string) config('ogeagleeye.log_level', 'info'));
        $minValue = self::LEVELS[$minimum] ?? self::LEVELS['info'];
        $levelValue = self::LEVELS[$level] ?? self::LEVELS['info'];

        return $levelValue >= $minValue;
    }

    /**
     * Drop non-JSON-serializable values so PayloadBuilder context encoding succeeds.
     *
     * @param  array<string, mixed>  $context
     * @return array<string, mixed>
     */
    protected function sanitizeContext(array $context): array
    {
        $out = [];

        foreach ($context as $key => $value) {
            if (! is_string($key) && ! is_int($key)) {
                continue;
            }

            if ($value instanceof Throwable) {
                $out[$key] = [
                    'class' => get_class($value),
                    'message' => $value->getMessage(),
                ];

                continue;
            }

            if (is_array($value)) {
                $out[$key] = $this->sanitizeContext($value);

                continue;
            }

            if (is_resource($value)) {
                continue;
            }

            if (is_object($value)) {
                if ($value instanceof Stringable) {
                    $out[$key] = (string) $value;
                }

                continue;
            }

            $out[$key] = $value;
        }

        return $out;
    }
}
