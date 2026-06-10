<?php

use App\Domain\Shared\Exceptions\DomainValidationException;
use App\Http\Middleware\AppTimeMiddleware;
use App\Http\Middleware\RequestIdMiddleware;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Validation\ValidationException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->api(
            append: [
                AppTimeMiddleware::class,
            ],
            prepend: [
                RequestIdMiddleware::class,
            ],
        );

        $middleware->alias([
            'appTime' => AppTimeMiddleware::class,
            'requestId' => RequestIdMiddleware::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->map(
            DomainValidationException::class,
            fn (DomainValidationException $exception): ValidationException => ValidationException::withMessages($exception->errors())
        );
    })->create();
