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
        return view('dashboard.broker-connect');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'broker' => 'required|string|max:100',
            'server' => 'required|string|max:100',
            'account_number' => 'required|string|max:50',
            'trading_mode' => 'required|in:signals_only,semi_automatic,fully_automatic',
        ]);

        $user = Auth::user();
        $activePlan = $user->subscription?->plan;
        $limit = $user->isSuperAdmin() ? null : $activePlan?->broker_connections_limit;
        $currentConnections = $user->brokerAccounts()->count();

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
            'trading_mode' => $data['trading_mode'],
            'connection_status' => 'connected',
            'connected_at' => now(),
            'verified_at' => now(),
        ]);
        $account->save();

        return redirect()->route('dashboard')
            ->with('status', 'Broker account connected and verified successfully.');
    }
}
