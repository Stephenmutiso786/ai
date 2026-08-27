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

        if ($user->isSuperAdmin()) {
            $subscription = null;
        }

        $check = $limiter->check($user, $subscription, $request);

        if (! $check->allowed) {
            return redirect()->route('dashboard')->with('run_error', $check->reason);
        }

        $instruments = Instrument::where('is_active', true)->get();
        if ($instruments->isEmpty()) {
            return redirect()->route('dashboard')->with('run_error', 'No active instruments are configured for analysis.');
        }

        $generated = 0;
        try {
            foreach ($instruments as $instrument) {
                $signalEngine->generateFor($instrument);
                $generated++;
            }
        } catch (\Throwable $e) {
            $message = 'AI analysis could not be completed right now. Please try again.';

            $response = redirect()->route('dashboard')->with('run_error', $message);

            if ($user->isAdmin()) {
                $response->with('run_error_admin', 'AI analysis failed: '.$e->getMessage());
            }

            return $response;
        }

        $limiter->record($user, $subscription, $request, [
            'instrument_count' => $instruments->count(),
            'signals_generated' => $generated,
        ]);

        $remaining = $subscription
            ? $limiter->check($user, $subscription->fresh(), $request)->remaining
            : null;
        $remainingLabel = $remaining === null ? 'unlimited' : $remaining;

        return redirect()->route('dashboard')
            ->with('status', "AI run complete across {$generated} instruments. Runs remaining this period: {$remainingLabel}.");
    }
}
