<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\Response;

class RequestIdMiddleware
{
    /**
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $requestId = trim((string) $request->headers->get('X-Request-Id'));

        if (strlen($requestId) > 255) {
            throw ValidationException::withMessages([
                'X-Request-Id' => 'The X-Request-Id cannot be longer than 255 characters',
            ]);
        }

        if ($requestId === '') {
            $requestId = (string) Str::uuid();
        }

        $request->headers->set('X-Request-Id', $requestId);
        $request->attributes->set('request_id', $requestId);

        Log::shareContext([
            'request_id' => $requestId,
        ]);

        $response = $next($request);
        $response->headers->set('X-Request-Id', $requestId);

        return $response;
    }
}
