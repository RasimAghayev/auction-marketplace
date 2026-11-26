<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

class RequestIdMiddleware
{
    public const HEADER = 'X-Request-ID';

    public const ATTRIBUTE = 'request_id';

    public function handle(Request $request, Closure $next): Response
    {
        $requestId = $this->resolveRequestId($request);

        $request->attributes->set(self::ATTRIBUTE, $requestId);

        Log::shareContext([
            'request_id' => $requestId,
            'method' => $request->method(),
            'path' => $request->path(),
        ]);

        $response = $next($request);

        return $response->withHeaders([self::HEADER => $requestId]);
    }

    private function resolveRequestId(Request $request): string
    {
        $incoming = $request->headers->get(self::HEADER);

        if (is_string($incoming) && $incoming !== '' && preg_match('/^[A-Za-z0-9._-]{1,64}$/', $incoming)) {
            return $incoming;
        }

        return (string) Str::uuid();
    }
}
