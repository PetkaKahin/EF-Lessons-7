<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AppTimeMiddleware
{
    /**
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $startedAt = microtime(true);

        $response = $next($request);

        $durationMs = (microtime(true) - $startedAt) * 1000;

        $response->headers->set('X-App-Time', number_format($durationMs, 1, '.', '').' ms');

        return $response;
    }
}
