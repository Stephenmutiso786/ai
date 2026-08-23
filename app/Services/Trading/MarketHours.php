<?php

namespace App\Services\Trading;

use Carbon\CarbonImmutable;

class MarketHours
{
    public function isOpen(?CarbonImmutable $now = null): bool
    {
        $now ??= CarbonImmutable::now('UTC');
        $day = (int) $now->dayOfWeekIso; // Mon=1 ... Sun=7
        $time = $now->format('H:i');

        if ($day === 6) {
            return false;
        }

        if ($day === 5 && $time >= '23:59') {
            return false;
        }

        if ($day === 7 && $time < '23:59') {
            return false;
        }

        return true;
    }
}
