<?php

return [

    // All plan prices in the database are stored in this currency.
    // Switched to USD as base since the Basic/Standard/Pro tiers are
    // priced in USD by design.
    'base_currency' => 'USD',

    // Fallback if detection fails entirely.
    'default_currency' => 'USD',

    /*
     * Static fallback rates: 1 USD = X units of target currency.
     *
     * These are illustrative and WILL drift. Set an fx_api_key in
     * Admin → Settings and CurrencyConverter::refreshRates() will pull
     * live rates on the scheduled job instead of using these — don't
     * ship static rates to production as-is, since stale FX rates mean
     * clients see a wrong price.
     */
    'rates' => [
        'USD' => 1.0,
        'KES' => 149.0,
        'EUR' => 0.92,
        'GBP' => 0.79,
        'NGN' => 1630.0,
        'ZAR' => 18.9,
        'UGX' => 3700.0,
        'TZS' => 2600.0,
        'GHS' => 15.0,
        'INR' => 87.0,
        'AED' => 3.67,
        'SAR' => 3.75,
        'CAD' => 1.37,
        'AUD' => 1.53,
        'PKR' => 279.0,
        'EGP' => 49.0,
        'RWF' => 1380.0,
    ],

    'symbols' => [
        'KES' => 'KSh', 'USD' => '$', 'EUR' => '€', 'GBP' => '£',
        'NGN' => '₦', 'ZAR' => 'R', 'UGX' => 'USh', 'TZS' => 'TSh',
        'GHS' => 'GH₵', 'INR' => '₹', 'AED' => 'AED', 'SAR' => 'SAR',
        'CAD' => 'CA$', 'AUD' => 'A$', 'PKR' => 'Rs', 'EGP' => 'E£', 'RWF' => 'FRw',
    ],

    // Decimal places to display per currency (0 for currencies where the
    // converted weekly price is naturally a large whole number).
    'decimals' => [
        'KES' => 0, 'UGX' => 0, 'TZS' => 0, 'NGN' => 0, 'RWF' => 0, 'PKR' => 0,
        'USD' => 2, 'EUR' => 2, 'GBP' => 2, 'ZAR' => 2, 'GHS' => 2, 'INR' => 2,
        'AED' => 2, 'SAR' => 2, 'CAD' => 2, 'AUD' => 2, 'EGP' => 2,
    ],

    // ISO 3166-1 alpha-2 country code => ISO 4217 currency code.
    // Extend this as you add markets. Anything not listed falls back
    // to 'default_currency' above.
    'country_currency' => [
        'KE' => 'KES', 'UG' => 'UGX', 'TZ' => 'TZS', 'RW' => 'RWF',
        'NG' => 'NGN', 'GH' => 'GHS', 'ZA' => 'ZAR', 'EG' => 'EGP',
        'US' => 'USD', 'GB' => 'GBP', 'IN' => 'INR', 'PK' => 'PKR',
        'AE' => 'AED', 'SA' => 'SAR', 'CA' => 'CAD', 'AU' => 'AUD',
        'DE' => 'EUR', 'FR' => 'EUR', 'ES' => 'EUR', 'IT' => 'EUR',
        'NL' => 'EUR', 'IE' => 'EUR', 'PT' => 'EUR', 'BE' => 'EUR',
    ],

    // Currencies selectable in the manual override dropdown.
    'selectable' => ['KES', 'USD', 'EUR', 'GBP', 'NGN', 'ZAR', 'UGX', 'TZS', 'GHS', 'INR', 'AED', 'CAD', 'AUD'],
];
