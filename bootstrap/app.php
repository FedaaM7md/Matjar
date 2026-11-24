<?php

// Matjar — © 2026 Fedaa Mohamed. All rights reserved. Proprietary.

use App\Http\Middleware\EnsureDemoEnabled;
use App\Http\Middleware\SecurityHeaders;
use App\Http\Middleware\SetLocale;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->web(append: [
            SetLocale::class,
            SecurityHeaders::class,
        ]);

        $middleware->alias([
            'demo' => EnsureDemoEnabled::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );

        // Ensure error pages (404, 500, …) also carry the security headers,
        // because they are rendered outside the normal middleware pipeline.
        $exceptions->respond(function ($response, $e, $request) {
            if ($response instanceof Response) {
                SecurityHeaders::apply($response, $request);
            }

            return $response;
        });
    })->create();
