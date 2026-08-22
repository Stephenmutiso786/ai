<?php

namespace App\Http\Controllers;

use App\Models\BrokerAccount;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

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

        $account = new BrokerAccount([
            'user_id' => Auth::id(),
            'broker' => $data['broker'],
            'platform' => 'MT5',
            'server' => $data['server'],
            'account_number' => $data['account_number'],
            'trading_mode' => $data['trading_mode'],
            // Connection stays "pending" until a real broker adapter
            // (see App\Services\Execution\BrokerAdapterInterface) verifies
            // it \u2014 there is no live handshake wired up in this scaffold.
            'connection_status' => 'pending',
        ]);
        $account->save();

        return redirect()->route('dashboard')
            ->with('status', 'Broker account saved. It stays in "pending" until a live adapter verifies the connection.');
    }
}
