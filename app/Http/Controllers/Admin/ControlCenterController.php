<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BrokerAccount;
use App\Models\AiSignal;
use App\Models\AiTrainingRun;
use App\Models\RiskProfile;
use App\Models\Subscription;
use App\Models\Trade;
use App\Models\User;
use App\Models\Setting;
use Illuminate\Support\Facades\DB;

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
        $latestSignal = AiSignal::with('instrument')->latest('generated_at')->first();
        $latestTrainingRun = AiTrainingRun::with(['model', 'dataset'])->latest('created_at')->first();
        $readiness = [
            'live_trading_enabled' => filter_var(config('live_trading.enabled', false), FILTER_VALIDATE_BOOL) ? 'ok' : 'blocked',
            'broker_execution_mode' => config('services.broker_execution_mode', 'paper') === 'live' ? 'ok' : 'blocked',
            'trained_model' => $latestTrainingRun?->model?->artifact_uri ? 'ok' : 'blocked',
            'fresh_signal' => $latestSignal ? 'ok' : 'blocked',
            'market_provider' => Setting::getValue('ai_market_data_provider') ? 'ok' : 'blocked',
        ];

        return view('admin.control-center', compact('stats', 'recentTrades', 'readiness', 'latestSignal', 'latestTrainingRun'));
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
