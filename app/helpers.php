<?php

use App\Models\Setting;

if (! function_exists('setting')) {
    /**
     * Read an API key / integration setting saved by an admin in
     * Admin -> Settings. Falls back to an env var of the same name if
     * nothing has been saved through the UI yet. This is the function
     * every integration (FX rates, geo-IP, market data, payments, MT5
     * bridge, email/SMS...) should call instead of env() directly, so a
     * key pasted into the admin panel actually takes effect without a
     * code deploy.
     */
    function setting(string $key, mixed $default = null): mixed
    {
        return Setting::getValue($key, $default);
    }
}
