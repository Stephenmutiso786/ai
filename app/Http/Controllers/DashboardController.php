<?php

namespace App\Http\Controllers;

use App\Models\Instrument;
use App\Models\AiBacktest;
use App\Models\AiSignal;
use App\Models\AiTrainingRun;
use App\Models\Trade;
use App\Models\Setting;
use App\Services\Execution\BrokerAdapterRegistry;
use App\Services\Signals\SignalEngine;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index(BrokerAdapterRegistry $registry)
    {
        $user = Auth::user();

        $instruments = Cache::remember('dashboard:instruments', now()->addSeconds(30), function () {
            return Instrument::with('latestSignal')->where('is_active', true)->get();
        });

        $openTrades = Trade::with('instrument')
            ->where('user_id', $user->id)
            ->where('status', 'open')
            ->latest('opened_at')
            ->get();

        $riskProfile = $user->riskProfile;
        $brokerAccount = $user->brokerAccounts()->where('connection_status', 'connected')->latest('verified_at')->first()
            ?? $user->brokerAccounts()->latest()->first();

        if ($brokerAccount && $brokerAccount->connection_status === 'connected' && (! $brokerAccount->last_synced_at || $brokerAccount->last_synced_at->lt(now()->subSeconds(45)))) {
            try {
                $snapshot = $registry->for($brokerAccount)->accountSnapshot($brokerAccount);
                $brokerAccount->forceFill([
                    'last_synced_at' => now(),
                    'balance' => data_get($snapshot, 'balance', $brokerAccount->balance),
                    'equity' => data_get($snapshot, 'equity', $brokerAccount->equity),
                    'margin_available' => data_get($snapshot, 'margin_available', $brokerAccount->margin_available),
                    'currency' => data_get($snapshot, 'currency', $brokerAccount->currency),
                ])->save();
            } catch (\Throwable) {
                // Keep the last known account values visible if a live refresh fails.
            }
        }

        $latestSignal = Cache::remember('dashboard:latest-signal', now()->addSeconds(20), fn () => AiSignal::with('instrument')->latest('generated_at')->first());
        $latestTrainingRun = Cache::remember('dashboard:latest-training-run', now()->addSeconds(30), fn () => AiTrainingRun::with(['model', 'dataset'])->latest('created_at')->first());
        $latestBacktest = Cache::remember('dashboard:latest-backtest', now()->addSeconds(30), fn () => AiBacktest::with('model')->latest('created_at')->first());
        $activeSymbol = $instruments->first()?->symbol;
        $timeframe = 'H1';
        $lastMarketCandle = $activeSymbol
            ? DB::table('market_data_candles')
                ->where('symbol', $activeSymbol)
                ->where('timeframe', $timeframe)
                ->orderByDesc('time')
                ->first()
            : null;
        $diagnostics = [
            'market_data' => [
                'status' => $lastMarketCandle ? 'ok' : 'critical',
                'label' => $lastMarketCandle ? 'Market data synced' : 'No candle data yet',
                'detail' => $lastMarketCandle?->time ? 'Last candle: '.$lastMarketCandle->time : 'The live market feed has not written candles into market_data_candles.',
            ],
            'ai_model' => [
                'status' => $latestTrainingRun?->model?->artifact_uri || $latestTrainingRun?->model?->status === 'live' ? 'ok' : 'critical',
                'label' => $latestTrainingRun?->model?->artifact_uri ? 'Model artifact available' : 'Model artifact missing',
                'detail' => $latestTrainingRun?->model?->artifact_uri
                    ? 'Artifact: '.$latestTrainingRun->model->artifact_uri
                    : 'The deployed AI service still needs a reachable trained artifact.',
            ],
            'provider' => [
                'status' => Setting::getValue('ai_market_data_provider') ? 'ok' : 'critical',
                'label' => Setting::getValue('ai_market_data_provider') ? 'Provider configured' : 'Provider missing',
                'detail' => Setting::getValue('ai_market_data_provider')
                    ? 'Active provider: '.Setting::getValue('ai_market_data_provider')
                    : 'Set the AI market-data provider in Settings.',
            ],
            'signal' => [
                'status' => $latestSignal ? 'ok' : 'critical',
                'label' => $latestSignal ? 'Last signal generated' : 'No live signal yet',
                'detail' => $latestSignal?->generated_at
                    ? 'Last signal: '.$latestSignal->generated_at->diffForHumans()
                    : 'No signal has been written recently.',
            ],
        ];

        return view('dashboard.index', compact(
            'instruments',
            'openTrades',
            'riskProfile',
            'brokerAccount',
            'latestSignal',
            'latestTrainingRun',
            'latestBacktest',
            'diagnostics'
        ));
    }

    public function refreshSignals(Request $request, SignalEngine $engine)
    {
        $request->validate(['timeframe' => 'nullable|string|max:10']);
        $timeframe = strtoupper($request->string('timeframe')->toString() ?: 'H1');

        $instruments = Instrument::where('is_active', true)->get();
        if ($instruments->isEmpty()) {
            return back()->with('run_error', 'No active instruments are available for live analysis.');
        }

        $generated = 0;
        $errors = [];

        foreach ($instruments as $instrument) {
            try {
                $engine->generateFor($instrument, $timeframe, true);
                $generated++;
            } catch (\Throwable $e) {
                $errors[] = "{$instrument->symbol}: ".$e->getMessage();
            }
        }

        if ($generated === 0) {
            return back()->with([
                'run_error' => 'Live analysis could not complete.',
                'run_error_admin' => implode(' | ', array_slice($errors, 0, 5)),
            ]);
        }

        return back()->with('status', "Refreshed {$generated} live signals on {$timeframe}.");
    }
}
