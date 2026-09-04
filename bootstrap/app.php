<?php

use App\Http\Middleware\EnsureTenantRole;
use App\Http\Middleware\SetLocale;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->alias([
            'tenant.role' => EnsureTenantRole::class,
            'locale' => SetLocale::class,
        ]);

        $middleware->redirectGuestsTo(function (Request $request): string {
            return $request->is('operator*') ? route('operator.login') : route('login');
        });

        $middleware->redirectUsersTo(function (Request $request): string {
            if ($request->is('operator*')) {
                return route('operator.dashboard');
            }
            $user = $request->user();
            return $user?->isHr() ? route('dashboard') : route('self-service');
        });
    })
    ->withExceptions(function (Exceptions $exceptions) {
        // Add reporting integrations here; never expose tenant data in exception responses.
    })
    ->create();
