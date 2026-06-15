<?php

use App\Domain\Shared\Exceptions\DomainValidationException;
use App\Http\Middleware\AppTimeMiddleware;
use App\Http\Middleware\RecordMetricsMiddleware;
use App\Http\Middleware\RequestIdMiddleware;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->prepend(RequestIdMiddleware::class);
        $middleware->append(RecordMetricsMiddleware::class);
        $middleware->throttleApi('api');

        // Гость на api/* не должен редиректиться на несуществующий маршрут login — иначе вместо 401 будет 500
        $middleware->redirectGuestsTo(
            static fn (Request $request): ?string => $request->is('api/*') ? null : route('login')
        );

        $middleware->api(
            append: [
                AppTimeMiddleware::class,
            ],
        );

        $middleware->alias([
            'appTime' => AppTimeMiddleware::class,
            'metrics' => RecordMetricsMiddleware::class,
            'requestId' => RequestIdMiddleware::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // API всегда отвечает JSON, иначе auth-исключение пытается редиректить на маршрут login и падает в 500
        $exceptions->shouldRenderJsonWhen(
            static fn (Request $request, Throwable $e): bool => $request->is('api/*') || $request->expectsJson()
        );

        $exceptions->map(
            DomainValidationException::class,
            fn (DomainValidationException $exception): ValidationException => ValidationException::withMessages($exception->errors())
        );
    })->create();
