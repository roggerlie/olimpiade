<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Spatie\Permission\Middleware\RoleMiddleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
        then: function (): void {
            Route::middleware('web')->group(__DIR__.'/../routes/auth.php');
            Route::middleware(['web', 'auth', 'role:admin'])
                ->prefix('admin')
                ->name('admin.')
                ->group(__DIR__.'/../routes/admin.php');
            Route::middleware(['web', 'auth', 'role:siswa'])
                ->name('cbt.')
                ->group(__DIR__.'/../routes/cbt.php');
        },
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'role' => RoleMiddleware::class,
        ]);

        // Two guests, two homes: an unauthenticated visit to /admin/* belongs
        // at the admin login, everything else at the student login. Likewise
        // an already-authenticated user hitting a guest route (e.g. revisiting
        // /login) lands on the dashboard for their own role.
        $middleware->redirectGuestsTo(
            fn (Request $request) => $request->is('admin/*') ? route('admin.login') : route('login')
        );
        $middleware->redirectUsersTo(
            fn (Request $request) => $request->user()?->hasRole('admin') ? route('admin.dashboard') : route('cbt.dashboard')
        );
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );
    })->create();
