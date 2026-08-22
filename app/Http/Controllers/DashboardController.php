<?php

namespace App\Http\Controllers;

use App\Models\Instrument;
use App\Models\Trade;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    public function index()
    {
        $user = Auth::user();

        $instruments = Instrument::with('latestSignal')->where('is_active', true)->get();

        $openTrades = Trade::with('instrument')
            ->where('user_id', $user->id)
            ->where('status', 'open')
            ->latest('opened_at')
            ->get();

        $riskProfile = $user->riskProfile;
        $brokerAccount = $user->brokerAccounts()->latest()->first();

        return view('dashboard.index', compact('instruments', 'openTrades', 'riskProfile', 'brokerAccount'));
    }
}
