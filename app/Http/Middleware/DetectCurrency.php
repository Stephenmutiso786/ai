<?php

namespace App\Http\Middleware;

use App\Services\Currency\CountryDetector;
use App\Services\Currency\CurrencyConverter;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\View;

class DetectCurrency
{
    public function __construct(
        protected CountryDetector $countryDetector,
        protected CurrencyConverter $converter,
    ) {}

    public function handle(Request $request, Closure $next)
    {
        // A manual override (set via CurrencyController@set) always wins,
        // and is sticky for the session — auto-detection should never
        // silently overwrite a choice the visitor already made.
        if (! session()->has('currency')) {
            $country = $this->countryDetector->detect($request);
            $currency = $this->converter->currencyForCountry($country);

            session([
                'currency' => $currency,
                'currency_country' => $country,
                'currency_auto_detected' => true,
            ]);
        }

        View::share('currentCurrency', session('currency'));
        View::share('currentCurrencyAutoDetected', session('currency_auto_detected', false));

        return $next($request);
    }
}
