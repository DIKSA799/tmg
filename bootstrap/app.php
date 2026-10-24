<?php

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
    ->withMiddleware(function (Middleware $middleware): void {
        // Config is not bound yet while middleware registers, so resolve the
        // console path lazily inside each redirect closure.
        $consolePath = fn (): string => trim((string) config('admin.path'), '/') ?: 'admin';

        $middleware->redirectGuestsTo(function (Request $request) use ($consolePath) {
            if ($request->is($consolePath()) || $request->is($consolePath().'/*')) {
                return route('admin.login');
            }

            if ($request->is('tmgamb') || $request->is('tmgamb/*')) {
                return route('tmgamb.login');
            }

            return route('login');
        });

        $middleware->redirectUsersTo(fn (Request $request) => $request->is($consolePath()) || $request->is($consolePath().'/*')
            ? route('admin.dashboard')
            : '/');
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );
    })->create();
