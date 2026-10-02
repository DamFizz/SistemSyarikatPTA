<?php

use App\Http\Middleware\CompressResponse;
use App\Http\Middleware\EnsureUserHasRole;
use App\Http\Middleware\TrustEdgeProxies;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Middleware\TrustProxies;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Railway (and sometimes Fastly in front of it) proxies every request. This lets
        // $request->ip() return the employee's real public IP for the office WiFi check.
        $middleware->replace(TrustProxies::class, TrustEdgeProxies::class);

        // Pages and JSON go out gzip-compressed (the hosting edge does not compress).
        $middleware->append(CompressResponse::class);

        $middleware->alias([
            'role' => EnsureUserHasRole::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );
    })->create();
