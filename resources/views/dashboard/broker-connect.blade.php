@extends('layouts.app')
@section('title', 'Connect broker — STETECH AI')
@section('content')
@include('dashboard._nav')

<div class="max-w-lg mx-auto px-6 py-14">
    <p class="font-mono text-xs tracking-[0.2em] text-brass mb-3">BROKER CONNECTION</p>
    <h1 class="font-display text-3xl mb-2">Connect your MT5 account</h1>
    <p class="text-sm text-muted mb-8 leading-relaxed">
        Your subscribed plan decides how this broker connection is configured.
        The app will lock the mode to the plan's allowed level so the account
        cannot be connected in the wrong trading mode.
    </p>

    @if ($errors->any())
        <div class="mb-6 border border-loss/40 bg-loss/10 text-loss text-sm rounded px-4 py-3">
            <ul class="list-disc list-inside">
                @foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach
            </ul>
        </div>
    @endif

    <form method="POST" action="{{ route('broker.store') }}" class="space-y-5">
        @csrf
        <div>
            <label class="font-mono text-xs text-muted block mb-1.5">BROKER</label>
            <input name="broker" value="{{ old('broker') }}" placeholder="e.g. HFM" class="w-full bg-panel border border-line rounded px-3 py-2.5 text-sm focus:border-brass outline-none" required>
        </div>
        <div>
            <label class="font-mono text-xs text-muted block mb-1.5">SERVER</label>
            <input name="server" value="{{ old('server') }}" placeholder="e.g. HFM-Live" class="w-full bg-panel border border-line rounded px-3 py-2.5 text-sm focus:border-brass outline-none" required>
        </div>
        <div>
            <label class="font-mono text-xs text-muted block mb-1.5">ACCOUNT NUMBER</label>
            <input name="account_number" value="{{ old('account_number') }}" placeholder="12345678" class="w-full bg-panel border border-line rounded px-3 py-2.5 text-sm focus:border-brass outline-none" required>
        </div>
        <div>
            <label class="font-mono text-xs text-muted block mb-1.5">TRADING MODE</label>
            <div class="w-full bg-panel border border-line rounded px-3 py-2.5 text-sm text-slate-200">
                {{ ucfirst(str_replace('_', ' ', $recommendedTradingMode)) }}
            </div>
            <p class="text-xs text-muted mt-1.5">
                @if($isSuperAdmin)
                    Super admin accounts can be configured for fully automatic execution.
                @elseif($plan)
                    {{ $plan->name }} plan allows up to {{ $recommendedTradingMode === 'signals_only' ? 'signals only' : ($recommendedTradingMode === 'semi_automatic' ? 'semi-automatic' : 'fully automatic') }}.
                @else
                    No active plan found, so the system will keep this on signals only.
                @endif
            </p>
        </div>
        <button type="submit" class="w-full bg-brass text-ink rounded py-2.5 text-sm font-medium hover:bg-brass/90 transition">Save connection</button>
    </form>
</div>

@endsection
