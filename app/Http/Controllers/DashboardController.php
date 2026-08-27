<?php

namespace App\Http\Controllers;

use App\Models\Instrument;
use App\Models\AiBacktest;
use App\Models\AiSignal;
use App\Models\AiTrainingRun;
use App\Models\Trade;
use App\Services\Execution\BrokerAdapterRegistry;
use App\Services\Signals\SignalEngine;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index(BrokerAdapterRegistry $registry)
    {
        $user = Auth::user();

        $instruments = Instrument::with('latestSignal')->where('is_active', true)->get();

        $openTrades = Trade::with('instrument')
            ->where('user_id', $user->id)
            ->where('status', 'open')
            ->latest('opened_at')
            ->get();

        $riskProfile = $user->riskProfile;
        $brokerAccount = $user->brokerAccounts()->where('connection_status', 'connected')->latest('verified_at')->first()
            ?? $user->brokerAccounts()->latest()->first();

        if ($brokerAccount && $brokerAccount->connection_status === 'connected') {
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

        $latestSignal = AiSignal::with('instrument')->latest('generated_at')->first();
        $latestTrainingRun = AiTrainingRun::with(['model', 'dataset'])->latest('created_at')->first();
        $latestBacktest = AiBacktest::with('model')->latest('created_at')->first();

        return view('dashboard.index', compact(
            'instruments',
            'openTrades',
            'riskProfile',
            'brokerAccount',
            'latestSignal',
            'latestTrainingRun',
            'latestBacktest'
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
                $engine->generateFor($instrument, $timeframe);
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
