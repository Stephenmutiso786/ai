<?php

namespace App\Http\Middleware;

use App\Services\Trading\MarketHours;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureMarketIsOpen
{
    public function __construct(protected MarketHours $marketHours) {}

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user || $user->isSuperAdmin() || $this->marketHours->isOpen()) {
            return $next($request);
        }

        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login')->with('status', 'Trading is closed until the market reopens.');
    }
}
