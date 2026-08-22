<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BrokerAccount;
use App\Models\RiskProfile;
use App\Models\Subscription;
use App\Models\Trade;
use App\Models\User;

class ControlCenterController extends Controller
{
    public function index()
    {
        $stats = [
            'clients' => User::where('role', 'client')->count(),
            'connected_accounts' => BrokerAccount::where('connection_status', 'connected')->count(),
            'active_subscriptions' => Subscription::where('status', 'active')->count(),
            'trades_today' => Trade::whereDate('created_at', now()->toDateString())->count(),
            'open_positions' => Trade::where('status', 'open')->count(),
            'halted_accounts' => RiskProfile::where('trading_halted', true)->count(),
        ];

        $recentTrades = Trade::with(['user', 'instrument'])->latest()->limit(25)->get();

        return view('admin.control-center', compact('stats', 'recentTrades'));
    }

    /**
     * Global kill switch. Halts every risk profile so the RiskEngine
     * rejects all further trades, regardless of what any signal says.
     */
    public function emergencyStopAll()
    {
        RiskProfile::query()->update(['trading_halted' => true]);

        \App\Models\AuditLog::create([
            'user_id' => auth()->id(),
            'action' => 'global_kill_switch_engaged',
        ]);

        return back()->with('status', 'All accounts halted. No new trades will be approved until manually re-enabled.');
    }
}
