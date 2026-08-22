<?php

namespace App\Services\Currency;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class CurrencyConverter
{
    public function currencyForCountry(string $countryCode): string
    {
        return config("currency.country_currency.{$countryCode}", config('currency.default_currency'));
    }

    /**
     * Convert an amount stored in the base currency (KES) into the target
     * currency using the current rate table.
     */
    public function convert(float $amountInBase, string $toCurrency): float
    {
        $rate = $this->rates()[$toCurrency] ?? $this->rates()[config('currency.default_currency')];

        return round($amountInBase * $rate, 4);
    }

    /**
     * Convert and format for display, e.g. "KSh 500" or "$3.35".
     * A price of 0 is treated as "Custom" (used for the Institutional plan).
     */
    public function format(float $amountInBase, string $toCurrency): string
    {
        if ($amountInBase <= 0) {
            return 'Custom';
        }

        $symbol = config("currency.symbols.{$toCurrency}", $toCurrency.' ');
        $decimals = config("currency.decimals.{$toCurrency}", 2);
        $converted = $this->convert($amountInBase, $toCurrency);

        return $symbol.' '.number_format($converted, $decimals);
    }

    protected function rates(): array
    {
        // Cache::rememberForever + a scheduled refresh job is the production
        // pattern; for now this reads straight from config with a cache
        // layer ready for refreshRates() to populate.
        return Cache::get('currency:rates') ?? config('currency.rates');
    }

    /**
     * Pull live rates and cache them. Runs every 6 hours via the schedule
     * in routes/console.php. Reads the provider + key an admin pasted
     * into Admin -> Settings; if neither is set, quietly keeps using the
     * static table in config/currency.php instead of failing loudly.
     */
    public function refreshRates(): void
    {
        $provider = setting('fx_provider');
        $apiKey = setting('fx_api_key');

        if (! $provider || ! $apiKey) {
            Log::info('stetech.currency_refresh_skipped', ['reason' => 'no fx_provider/fx_api_key set in Admin > Settings']);

            return;
        }

        try {
            $response = match (strtolower($provider)) {
                'exchangerate.host', 'exchangerate' => Http::timeout(5)->get('https://api.exchangerate.host/live', [
                    'access_key' => $apiKey, 'source' => config('currency.base_currency'),
                ]),
                'openexchangerates', 'open exchange rates' => Http::timeout(5)->get('https://openexchangerates.org/api/latest.json', [
                    'app_id' => $apiKey, 'base' => config('currency.base_currency'),
                ]),
                default => Http::timeout(5)->get($provider, ['api_key' => $apiKey, 'base' => config('currency.base_currency')]),
            };

            if ($response->ok()) {
                $rates = $response->json('rates') ?? $response->json('quotes');
                if (is_array($rates) && ! empty($rates)) {
                    Cache::put('currency:rates', $rates, now()->addHours(6));
                    Log::info('stetech.currency_refresh_ok', ['provider' => $provider, 'pairs' => count($rates)]);
                }
            } else {
                Log::warning('stetech.currency_refresh_failed', ['provider' => $provider, 'status' => $response->status()]);
            }
        } catch (\Throwable $e) {
            Log::warning('stetech.currency_refresh_failed', ['provider' => $provider, 'error' => $e->getMessage()]);
        }
    }
}
