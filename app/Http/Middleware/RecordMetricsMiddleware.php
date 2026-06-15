<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Models\RequestMetric;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class RecordMetricsMiddleware
{
    /**
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $startedAt = microtime(true);
        $response = null;

        try {
            $response = $next($request);

            return $response;
        } finally {
            try {
                RequestMetric::create([
                    'method' => $request->method(),
                    'path' => $request->path(),
                    'status_code' => $response?->getStatusCode() ?? 500,
                    'response_time_ms' => (microtime(true) - $startedAt) * 1000,
                ]);
            } catch (Throwable) {
            }
        }
    }
}
