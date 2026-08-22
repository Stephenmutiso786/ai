<?php

namespace App\Services\Usage;

use Illuminate\Http\Request;
use Illuminate\Support\Str;

/**
 * Builds a best-effort device identifier for the demo one-run limit.
 *
 * IMPORTANT: this is NOT a MAC address. No website -- not this one, not
 * any other -- can read a visitor's MAC address; browsers never expose
 * it, on any OS. What we actually do is combine three weaker signals:
 *
 *   1. A long-lived, HttpOnly signed cookie ("device token") issued on
 *      first visit and re-sent on every request from that browser.
 *   2. The request's IP address.
 *   3. The browser's User-Agent string.
 *
 * This is sticky enough to stop casual demo-limit bypassing (new tab,
 * new account, same browser) but a determined user can still defeat it
 * with a different browser, private/incognito mode, or a VPN. If you
 * need a harder guarantee, require phone-number verification (e.g. via
 * the SMS provider configured in Admin -> Settings) before granting the
 * demo run, rather than relying on device signals alone.
 */
class DeviceFingerprint
{
    public const COOKIE_NAME = 'stetech_device';

    public function resolve(Request $request): string
    {
        $token = $request->cookie(self::COOKIE_NAME) ?: (string) Str::uuid();

        $raw = $token.'|'.$request->userAgent();

        return hash('sha256', $raw);
    }

    public function cookieToken(Request $request): string
    {
        return $request->cookie(self::COOKIE_NAME) ?: (string) Str::uuid();
    }
}
