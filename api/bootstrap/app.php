<?php

declare(strict_types=1);

use App\Exceptions\DomainException;
use App\Http\Middleware\ResolveCompany;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Applied per route group in routes/api.php so the login route, which
        // issues the token, stays open.
        $middleware->alias([
            'tenant' => ResolveCompany::class,
        ]);

        // The tenant must be resolved before route-model binding runs, so a
        // cross-tenant {booking} or {resource} id is filtered by the global
        // scope rather than silently loaded.
        $middleware->prependToPriorityList(
            before: Illuminate\Routing\Middleware\SubstituteBindings::class,
            prepend: ResolveCompany::class,
        );
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );

        // Every domain exception carries its own HTTP status and public message.
        $exceptions->render(function (DomainException $e, Request $request) {
            return response()->json([
                'message' => $e->getMessage(),
                'error' => class_basename($e),
            ], $e->status());
        });
    })->create();
