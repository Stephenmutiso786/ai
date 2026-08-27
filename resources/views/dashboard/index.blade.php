@extends('layouts.app')
@section('title', 'Dashboard — STETECH AI')
@section('content')
@include('dashboard._nav')

<div class="max-w-7xl mx-auto px-4 sm:px-6 py-6 sm:py-8">

    @if(session('status'))
        <div class="mb-6 border border-brass/40 bg-brass/10 text-brass text-sm rounded px-4 py-3">{{ session('status') }}</div>
    @endif
    @if(session('run_error'))
        <div class="mb-6 border border-loss/40 bg-loss/10 text-loss text-sm rounded px-4 py-3">{{ session('run_error') }}</div>
        @if(auth()->user()->isAdmin() && session('run_error_admin'))
            <div class="mb-6 border border-line bg-ink/60 text-muted text-xs rounded px-4 py-3 font-mono">
                Admin detail: {{ session('run_error_admin') }}
            </div>
        @endif
    @endif

    <!-- Usage / run status -->
    @php
        $subscription = auth()->user()->subscription;
        $plan = $subscription?->plan;
        $limit = $subscription?->effectiveRunsPerWeek();
        $used = $subscription?->runs_used_this_period ?? 0;
        $planName = $plan?->name ?? 'No plan';
        $brokerLimit = $plan?->broker_connections_limit;
        $brokerCount = auth()->user()->brokerAccounts()->count();
    @endphp
    <div class="bg-panel border border-line rounded-lg p-4 sm:p-5 mb-8 flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
        <div>
            <p class="font-mono text-[11px] tracking-wide text-muted mb-1">PLAN</p>
            <p class="text-lg">
                {{ $planName }}
                @if($subscription?->hasCustomTerms())<span class="text-brass text-xs font-mono ml-2">CUSTOM TERMS</span>@endif
            </p>
            <p class="text-sm text-muted font-mono mt-1">
                @if($limit === null)
                    Unlimited runs this week
                @else
                    {{ $used }} / {{ $limit }} runs used this week
                @endif
            </p>
            <p class="text-xs text-muted font-mono mt-2">
                Broker connections: {{ $brokerCount }}
                @if(auth()->user()->isSuperAdmin())
                    <span class="text-brass">/ unlimited as super admin</span>
                @elseif($brokerLimit !== null)
                    <span>/ {{ $brokerLimit }} max on this plan</span>
                @endif
            </p>
        </div>
        <div class="flex flex-wrap gap-3">
            <form method="POST" action="{{ route('run.trigger') }}">
                @csrf
                <button class="bg-brass text-ink px-5 py-2.5 rounded font-medium text-sm hover:bg-brass/90 transition">Run AI analysis</button>
            </form>
            <form method="POST" action="{{ route('dashboard.refresh-signals') }}">
                @csrf
                <button class="border border-line px-5 py-2.5 rounded font-medium text-sm hover:border-brass/60 hover:text-brass transition">Refresh live signals</button>
            </form>
            <a href="{{ route('trading.workspace') }}" class="border border-line px-5 py-2.5 rounded font-medium text-sm hover:border-brass/60 hover:text-brass transition">Open Trading Workspace</a>
        </div>
    </div>

    <!-- Account summary -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-8">
        @php
            $summary = [
                ['Balance', $brokerAccount?->balance ? number_format((float) $brokerAccount->balance, 2) : '—', null],
                ['Equity', $brokerAccount?->equity ? number_format((float) $brokerAccount->equity, 2) : '—', 'gain'],
                ['Margin available', $brokerAccount?->margin_available ? number_format((float) $brokerAccount->margin_available, 2) : '—', null],
                ['Currency', $brokerAccount?->currency ?? '—', null],
            ];
        @endphp
        @foreach ($summary as [$label,$val,$tone])
            <div class="bg-panel border border-line rounded-lg p-5">
                <p class="font-mono text-[11px] tracking-wide text-muted mb-2">{{ strtoupper($label) }}</p>
                <p class="font-mono text-xl {{ $tone === 'gain' ? 'text-gain' : 'text-slate-100' }}">{{ $val }}</p>
            </div>
        @endforeach
    </div>

    <div class="bg-panel border border-line rounded-lg p-5 mb-8">
        <div class="flex items-center justify-between gap-3 mb-4">
            <div>
                <p class="font-mono text-[11px] tracking-wide text-muted">LIVE DIAGNOSTICS</p>
                <p class="text-sm text-muted mt-1">Real status from candle sync, model availability, provider config, and the latest signal.</p>
            </div>
            <a href="{{ route('admin.settings') }}" class="text-xs text-brass hover:underline">Edit settings</a>
        </div>
        <div class="grid md:grid-cols-2 xl:grid-cols-4 gap-4">
            @foreach ($diagnostics as $key => $item)
                <div class="border rounded-lg p-4 {{ $item['status'] === 'ok' ? 'border-gain/30 bg-gain/5' : 'border-loss/30 bg-loss/5' }}">
                    <div class="flex items-center justify-between gap-3">
                        <p class="font-mono text-[10px] tracking-wide text-muted">{{ strtoupper(str_replace('_', ' ', $key)) }}</p>
                        <span class="font-mono text-[10px] px-2 py-0.5 rounded border {{ $item['status'] === 'ok' ? 'border-gain/40 text-gain' : 'border-loss/40 text-loss' }}">
                            {{ strtoupper($item['status']) }}
                        </span>
                    </div>
                    <p class="mt-3 text-sm">{{ $item['label'] }}</p>
                    <p class="mt-2 text-xs text-muted">{{ $item['detail'] }}</p>
                </div>
            @endforeach
        </div>
    </div>

    @if(auth()->user()->isAdmin())
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-4 mb-8">
            <div class="bg-panel border border-line rounded-lg p-5">
                <p class="font-mono text-[11px] tracking-wide text-muted mb-2">LAST SIGNAL</p>
                @if($latestSignal)
                    <p class="text-lg font-medium">{{ strtoupper($latestSignal->direction) }} {{ $latestSignal->instrument?->symbol }}</p>
                    <p class="text-sm text-muted font-mono mt-1">confidence {{ $latestSignal->confidence }}% · {{ $latestSignal->generated_at?->diffForHumans() }}</p>
                    <p class="text-xs text-muted mt-2">{{ $latestSignal->reasoning }}</p>
                @else
                    <p class="text-sm text-muted">No signal has been generated yet.</p>
                @endif
            </div>
            <div class="bg-panel border border-line rounded-lg p-5">
                <p class="font-mono text-[11px] tracking-wide text-muted mb-2">LAST TRAINING RUN</p>
                @if($latestTrainingRun)
                    <p class="text-lg font-medium">{{ $latestTrainingRun->model?->name ?? 'Model' }} {{ $latestTrainingRun->model?->version }}</p>
                    <p class="text-sm text-muted font-mono mt-1">{{ strtoupper($latestTrainingRun->status) }} · {{ $latestTrainingRun->created_at?->diffForHumans() }}</p>
                    @if($latestTrainingRun->error_message)
                        <p class="text-xs text-loss mt-2">{{ $latestTrainingRun->error_message }}</p>
                    @endif
                @else
                    <p class="text-sm text-muted">No training runs yet.</p>
                @endif
            </div>
            <div class="bg-panel border border-line rounded-lg p-5">
                <p class="font-mono text-[11px] tracking-wide text-muted mb-2">LAST BACKTEST</p>
                @if($latestBacktest)
                    <p class="text-lg font-medium">{{ $latestBacktest->model?->name ?? 'Model' }} · {{ $latestBacktest->instrument_symbol }}</p>
                    <p class="text-sm text-muted font-mono mt-1">{{ strtoupper($latestBacktest->status) }} · {{ $latestBacktest->created_at?->diffForHumans() }}</p>
                    @if($latestBacktest->error_message)
                        <p class="text-xs text-loss mt-2">{{ $latestBacktest->error_message }}</p>
                    @endif
                @else
                    <p class="text-sm text-muted">No backtests yet.</p>
                @endif
            </div>
        </div>
    @endif

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- AI Market Status -->
        <div class="lg:col-span-2 bg-panel border border-line rounded-lg overflow-hidden">
            <div class="flex items-center justify-between px-5 py-3 border-b border-line">
                <span class="font-mono text-[11px] tracking-[0.15em] text-muted">AI MARKET STATUS</span>
                <span class="flex items-center gap-1.5 font-mono text-[11px] text-gain"><span class="w-1.5 h-1.5 rounded-full bg-gain animate-pulse"></span> LIVE</span>
            </div>
            <div class="divide-y divide-line">
                @forelse ($instruments as $instrument)
                    @php $signal = $instrument->latestSignal; @endphp
                    <div class="flex flex-wrap items-center gap-2 px-4 sm:px-5 py-3.5">
                        <span class="font-mono text-sm w-20 shrink-0">{{ $instrument->symbol }}</span>
                        @if($signal)
                            <span class="font-mono text-xs px-2 py-0.5 rounded border w-16 text-center
                                {{ $signal->direction === 'buy' ? 'border-gain/40 text-gain' : ($signal->direction === 'sell' ? 'border-loss/40 text-loss' : 'border-line text-muted') }}">
                                {{ strtoupper($signal->direction) }}</span>
                            <div class="flex-1 min-w-[120px] h-1.5 bg-line rounded-full overflow-hidden">
                                <div class="h-full {{ $signal->direction === 'buy' ? 'bg-gain' : ($signal->direction === 'sell' ? 'bg-loss' : 'bg-muted') }}" style="width: {{ $signal->confidence }}%"></div>
                            </div>
                            <span class="font-mono text-xs text-muted w-10 text-right shrink-0">{{ $signal->confidence }}%</span>
                            <span class="font-mono text-[10px] text-muted w-full sm:w-auto sm:ml-auto">updated {{ $signal->generated_at?->diffForHumans() }}</span>
                        @else
                            <span class="font-mono text-xs text-muted">no signal yet</span>
                        @endif
                    </div>
                @empty
                    <p class="px-5 py-6 text-sm text-muted">No instruments configured yet. Run the database seeder.</p>
                @endforelse
            </div>
            <div class="px-5 py-3 bg-ink/60 font-mono text-[10px] text-muted">
                Signals are proposals only. Every trade below passed through the risk engine — see rejections in the audit log.
            </div>
        </div>

        <!-- Risk profile + broker -->
        <div class="space-y-6">
            <div class="bg-panel border border-line rounded-lg p-4 sm:p-5">
                <p class="font-mono text-[11px] tracking-wide text-muted mb-4">RISK PROFILE</p>
                @if($riskProfile)
                    <dl class="space-y-2.5 font-mono text-sm">
                        <div class="flex justify-between"><dt class="text-muted">Max risk/trade</dt><dd>{{ $riskProfile->max_risk_per_trade_pct }}%</dd></div>
                        <div class="flex justify-between"><dt class="text-muted">Max daily loss</dt><dd>{{ $riskProfile->max_daily_loss_pct }}%</dd></div>
                        <div class="flex justify-between"><dt class="text-muted">Max open positions</dt><dd>{{ $riskProfile->max_open_positions }}</dd></div>
                        <div class="flex justify-between"><dt class="text-muted">Status</dt><dd class="{{ $riskProfile->trading_halted ? 'text-loss' : 'text-gain' }}">{{ $riskProfile->trading_halted ? 'HALTED' : 'ACTIVE' }}</dd></div>
                    </dl>
                @else
                    <p class="text-sm text-muted">Default conservative profile will be created on your first signal check.</p>
                @endif
            </div>

            <div class="bg-panel border border-line rounded-lg p-4 sm:p-5">
                <p class="font-mono text-[11px] tracking-wide text-muted mb-4">BROKER ACCOUNT</p>
                @if($brokerAccount)
                    <p class="text-sm mb-1">{{ $brokerAccount->broker }} · {{ $brokerAccount->platform }}</p>
                    <p class="font-mono text-xs text-muted mb-4">{{ $brokerAccount->server }} / ****{{ substr($brokerAccount->account_number, -4) }}</p>
                    <div class="flex items-center gap-2">
                        <span class="font-mono text-xs px-2 py-1 rounded border border-line text-muted">{{ strtoupper($brokerAccount->connection_status) }}</span>
                        @if($brokerAccount->isVerified())
                            <span class="font-mono text-[10px] px-2 py-1 rounded border border-gain/40 text-gain">VERIFIED</span>
                        @endif
                    </div>
                @else
                    <p class="text-sm text-muted mb-4">No broker account connected yet.</p>
                    <a href="{{ route('broker.connect') }}" class="block text-center bg-brass text-ink rounded py-2 text-sm font-medium hover:bg-brass/90 transition">Connect account</a>
                @endif
            </div>
        </div>
    </div>

    <!-- Open trades -->
    <div class="mt-6 bg-panel border border-line rounded-lg overflow-hidden">
        <div class="px-5 py-3 border-b border-line font-mono text-[11px] tracking-[0.15em] text-muted">OPEN TRADES</div>
        <div class="divide-y divide-line">
            @forelse ($openTrades as $trade)
                <div class="flex items-center justify-between px-5 py-3 text-sm">
                    <span class="font-mono">{{ $trade->instrument->symbol }}</span>
                    <span class="font-mono text-xs {{ $trade->side === 'buy' ? 'text-gain' : 'text-loss' }}">{{ strtoupper($trade->side) }}</span>
                    <span class="text-muted font-mono text-xs">lot {{ $trade->lot_size }}</span>
                    <span class="text-muted font-mono text-xs">opened {{ $trade->opened_at?->diffForHumans() }}</span>
                </div>
            @empty
                <p class="px-5 py-6 text-sm text-muted">No open trades yet.</p>
            @endforelse
        </div>
    </div>
</div>

@endsection
