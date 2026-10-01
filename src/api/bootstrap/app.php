<?php

use App\Http\Middleware\EnsurePasswordIsChanged;
use App\Http\Middleware\EnsureTwoFactorIsEnabled;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Sanctum cookie sessions for requests from the Nuxt SPA.
        $middleware->statefulApi();

        $middleware->alias([
            'password.changed' => EnsurePasswordIsChanged::class,
            'two-factor.enforced' => EnsureTwoFactorIsEnabled::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // API-only: always answer with JSON, never an HTML error page.
        $exceptions->shouldRenderJsonWhen(fn ($request) => $request->is('api/*') || $request->expectsJson());
    })->create();
