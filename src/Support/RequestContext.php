<?php

namespace OGEagleEye\Laravel\Support;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Request-scoped enrichment merged into captured events.
 */
class RequestContext
{
    /** @var array<string, mixed> */
    protected array $extra = [];

    public function merge(array $context): void
    {
        $this->extra = array_replace_recursive($this->extra, $context);
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(?Request $request = null): array
    {
        $request ??= request();
        $context = $this->extra;

        $tags = $context['tags'] ?? [];
        if (! is_array($tags)) {
            $tags = [];
        }

        if ($request instanceof Request) {
            $route = $request->route();
            if ($route !== null) {
                $name = method_exists($route, 'getName') ? $route->getName() : null;
                if (is_string($name) && $name !== '') {
                    $tags['route'] = $name;
                } elseif (method_exists($route, 'uri')) {
                    $tags['route'] = (string) $route->uri();
                }
            }
        }

        $release = config('ogeagleeye.release');
        if (is_string($release) && $release !== '') {
            $tags['release'] = $release;
        }

        if ($tags !== []) {
            $context['tags'] = $tags;
        }

        $user = Auth::user();
        if ($user !== null) {
            $userPayload = [];
            if (isset($user->id)) {
                $userPayload['id'] = (string) $user->id;
            }
            if (isset($user->email) && is_string($user->email)) {
                $userPayload['email'] = $user->email;
            }
            if ($userPayload !== []) {
                $context['user'] = array_merge($context['user'] ?? [], $userPayload);
            }
        }

        return $context;
    }
}
