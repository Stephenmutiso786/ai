<?php

namespace App\Http\Controllers;

use App\Models\Instrument;
use App\Services\Signals\SignalEngine;
use App\Services\Usage\RunLimiter;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class SignalRunController extends Controller
{
    public function run(Request $request, RunLimiter $limiter, SignalEngine $signalEngine)
    {
        $user = Auth::user();
        $subscription = $user->subscription;

        $check = $limiter->check($user, $subscription, $request);

        if (! $check->allowed) {
            return redirect()->route('dashboard')->with('run_error', $check->reason);
        }

        $instruments = Instrument::where('is_active', true)->get();
        foreach ($instruments as $instrument) {
            $signalEngine->generateFor($instrument);
        }

        $limiter->record($user, $subscription, $request, [
            'instrument_count' => $instruments->count(),
        ]);

        $remaining = $subscription
            ? $limiter->check($user, $subscription->fresh(), $request)->remaining
            : null;
        $remainingLabel = $remaining === null ? 'unlimited' : $remaining;

        return redirect()->route('dashboard')
            ->with('status', "AI run complete across {$instruments->count()} instruments. Runs remaining this period: {$remainingLabel}.");
    }
}
