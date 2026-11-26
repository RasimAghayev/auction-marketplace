<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpFoundation\Response;

class RateLimitMiddleware
{
    public function handle(Request $request, Closure $next, int $maxAttempts = 60, float $decayMinutes = 1): Response
    {
        $routeName = $request->route()?->getName() ?: $request->method().':'.$request->path();
        $identity = $request->user()?->getAuthIdentifier() ?: $request->ip();
        $key = 'rate_limit:'.$identity.':'.$routeName;

        $attempts = Cache::get($key, 0);

        if ($attempts >= $maxAttempts) {
            $retryAfter = method_exists(Cache::getStore(), 'ttl') ? Cache::ttl($key) : null;

            return response()->json([
                'message' => 'Too many requests. Please try again later.',
                'retry_after' => $retryAfter,
            ], 429);
        }

        if ($attempts === 0) {
            Cache::put($key, 1, now()->addMinutes($decayMinutes));
        } else {
            Cache::increment($key);
        }

        $response = $next($request);

        return $response->withHeaders([
            'X-RateLimit-Limit' => $maxAttempts,
            'X-RateLimit-Remaining' => max(0, $maxAttempts - $attempts - 1),
            'X-RateLimit-Reset' => now()->addMinutes($decayMinutes)->timestamp,
        ]);
    }
}
