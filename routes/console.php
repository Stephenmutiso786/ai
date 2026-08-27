<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Keeps displayed prices from drifting once a live FX provider is wired
// into CurrencyConverter::refreshRates().
Schedule::call(fn () => app(\App\Services\Currency\CurrencyConverter::class)->refreshRates())
    ->everySixHours();

// Production broker state reconciliation. Queue workers must be running.
Schedule::command('stetech:brokers-reconcile')->everyMinute()->withoutOverlapping();

Schedule::command('subscriptions:expire')->hourly()->withoutOverlapping();

// Continuous production dependency monitoring and incident creation.
Schedule::command('stetech:monitor')->everyMinute()->withoutOverlapping();

// Real candle sync for the TradingView-style workspace and AI training pipeline.
Schedule::command('ai:sync-market-candles --timeframe=H1 --limit=500')
    ->everyFiveMinutes()
    ->withoutOverlapping();

// Generate fresh signals from the latest live market data.
Schedule::command('ai:run-live-analysis --timeframe=H1')
    ->everyTenMinutes()
    ->withoutOverlapping();
