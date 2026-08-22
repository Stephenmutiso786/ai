<?php
namespace App\Http\Middleware;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RequireLiveTradingConfirmation
{
    public function handle(Request $request, Closure $next): Response
    {
        if (!filter_var(config('live_trading.enabled', false), FILTER_VALIDATE_BOOL)) {
            return response()->json(['message' => 'Live trading is disabled by production safety control.'], 423);
        }
        if ($request->header('X-Live-Trading-Confirm') !== 'CONFIRM') {
            return response()->json(['message' => 'Explicit live-trading confirmation is required.'], 428);
        }
        return $next($request);
    }
}
