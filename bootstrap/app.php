<?php

use App\Http\Middleware\AssignDeviceCookie;
use App\Http\Middleware\DetectCurrency;
use App\Http\Middleware\EnsureMarketIsOpen;
use App\Http\Middleware\TrustProxies;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->alias([
            'live.trading.confirm' => \App\Http\Middleware\RequireLiveTradingConfirmation::class,
            'market.open' => EnsureMarketIsOpen::class,
        ]);
        $middleware->web(append: [
            TrustProxies::class,
            DetectCurrency::class,
            AssignDeviceCookie::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();
