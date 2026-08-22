<?php

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
    ->withMiddleware(function (Middleware $middleware) {

        // ── CORS untuk Flutter Web ──
        $middleware->use([
            \Illuminate\Http\Middleware\HandleCors::class,
        ]);

        $middleware->alias([
            'admin'          => \App\Http\Middleware\AdminMiddleware::class,
            'admin.web'      => \App\Http\Middleware\AdminWebMiddleware::class,
            'kasir'          => \App\Http\Middleware\KasirMiddleware::class,
            'kasir_or_admin' => \App\Http\Middleware\KasirOrAdminMiddleware::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();