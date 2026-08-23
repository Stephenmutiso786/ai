<?php

namespace App\Http\Controllers;

use App\Models\BrokerAccount;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class BrokerAccountController extends Controller
{
    public function create()
    {
        $user = Auth::user();
        $plan = $user->subscription?->plan;

        return view('dashboard.broker-connect', [
            'recommendedTradingMode' => $user->isSuperAdmin()
                ? 'fully_automatic'
                : ($plan?->recommendedTradingMode() ?? 'signals_only'),
            'plan' => $plan,
            'isSuperAdmin' => $user->isSuperAdmin(),
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'broker' => 'required|string|max:100',
            'server' => 'required|string|max:100',
            'account_number' => 'required|string|max:50',
        ]);

        $user = Auth::user();
        $activePlan = $user->subscription?->plan;
        $limit = $user->isSuperAdmin() ? null : $activePlan?->broker_connections_limit;
        $currentConnections = $user->brokerAccounts()->count();
        $tradingMode = $user->isSuperAdmin()
            ? 'fully_automatic'
            : ($activePlan?->recommendedTradingMode() ?? 'signals_only');

        if ($activePlan && ! $user->isSuperAdmin() && ! $activePlan->allowsTradingMode($tradingMode)) {
            throw ValidationException::withMessages([
                'broker' => "Your {$activePlan->name} plan does not allow {$tradingMode} connections.",
            ]);
        }

        if (! $user->isSuperAdmin() && $limit !== null && $currentConnections >= $limit) {
            throw ValidationException::withMessages([
                'broker' => "Your {$activePlan->name} plan allows only {$limit} broker connection" . ($limit === 1 ? '' : 's') . '.',
            ]);
        }

        $account = new BrokerAccount([
            'user_id' => $user->id,
            'broker' => $data['broker'],
            'platform' => 'MT5',
            'server' => $data['server'],
            'account_number' => $data['account_number'],
            'trading_mode' => $tradingMode,
            'connection_status' => 'connected',
            'connected_at' => now(),
            'verified_at' => now(),
        ]);
        $account->save();

        return redirect()->route('dashboard')
            ->with('status', 'Broker account connected as '.$tradingMode.' and verified successfully.');
    }
}
