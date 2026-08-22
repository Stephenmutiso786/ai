<?php

namespace App\Services\Currency;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Resolves a visitor's country from their IP address.
 *
 * Uses ip-api.com's free tier (no key required, ~45 req/min) as a default.
 * For production volume, swap in MaxMind GeoLite2 (a local DB lookup — no
 * outbound request per visitor, no rate limit) or a paid geo-IP provider.
 * Swap point is resolveCountryFromIp() below.
 */
class CountryDetector
{
    public function detect(Request $request): string
    {
        $ip = $request->ip();

        // Local/dev/private IPs won't resolve to anything real — fall back
        // to config rather than guessing, so local development isn't broken.
        if ($this->isPrivateOrLocal($ip)) {
            return config('app.dev_default_country', 'KE');
        }

        return Cache::remember("geo:country:{$ip}", now()->addHours(12), function () use ($ip, $request) {
            return $this->resolveCountryFromIp($ip) ?? $this->fallbackFromAcceptLanguage($request) ?? 'KE';
        });
    }

    protected function resolveCountryFromIp(string $ip): ?string
    {
        $apiKey = setting('geoip_api_key');

        try {
            // If an admin has pasted a paid geo-IP key into Settings, use
            // it; otherwise fall back to the free ip-api.com tier so the
            // platform still works with zero configuration.
            $response = $apiKey
                ? Http::timeout(2)->get('https://ipapi.co/'.$ip.'/json/', ['key' => $apiKey])
                : Http::timeout(2)->get("http://ip-api.com/json/{$ip}", ['fields' => 'status,countryCode']);

            if (! $response->ok()) {
                return null;
            }

            return $apiKey
                ? $response->json('country_code')
                : ($response->json('status') === 'success' ? $response->json('countryCode') : null);
        } catch (\Throwable $e) {
            Log::warning('stetech.geo_lookup_failed', ['ip' => $ip, 'error' => $e->getMessage()]);

            return null;
        }
    }

    /**
     * Very rough last-resort fallback using the browser's Accept-Language
     * header (e.g. "en-KE" -> KE) if the IP lookup fails outright.
     */
    protected function fallbackFromAcceptLanguage(Request $request): ?string
    {
        $header = $request->header('Accept-Language', '');
        if (preg_match('/-([A-Z]{2})\b/', $header, $m)) {
            return $m[1];
        }

        return null;
    }

    protected function isPrivateOrLocal(?string $ip): bool
    {
        if (! $ip) {
            return true;
        }

        return ! filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE);
    }
}
