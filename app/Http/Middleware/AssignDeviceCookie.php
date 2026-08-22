<?php

namespace App\Http\Middleware;

use App\Services\Usage\DeviceFingerprint;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cookie;

class AssignDeviceCookie
{
    public function handle(Request $request, Closure $next)
    {
        $response = $next($request);

        if (! $request->cookie(DeviceFingerprint::COOKIE_NAME)) {
            $token = (new DeviceFingerprint)->cookieToken($request);
            // 5 years, HttpOnly so client-side JS can't read or forge it.
            $response->withCookie(Cookie::make(DeviceFingerprint::COOKIE_NAME, $token, 60 * 24 * 365 * 5, null, null, null, true));
        }

        return $response;
    }
}
