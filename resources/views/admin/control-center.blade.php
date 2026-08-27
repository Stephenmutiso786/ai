@extends('layouts.app')
@section('title', 'Control Center — STETECH AI')
@section('content')
@include('dashboard._nav')

<div class="max-w-7xl mx-auto px-6 py-8">
    @if(session('status'))
        <div class="mb-6 border border-loss/40 bg-loss/10 text-loss text-sm rounded px-4 py-3">{{ session('status') }}</div>
    @endif
    @if(session('run_error_admin'))
        <div class="mb-6 border border-line bg-ink/60 text-muted text-xs rounded px-4 py-3 font-mono">
            AI detail: {{ session('run_error_admin') }}
        </div>
    @endif

    <div class="bg-panel border border-line rounded-lg p-5 mb-8">
        <div class="flex items-center justify-between gap-3 mb-4">
            <div>
                <p class="font-mono text-[11px] tracking-[0.15em] text-muted">TRADE READINESS</p>
                <p class="text-sm text-muted mt-1">Exact blockers for live execution. All must be green before a live order can be sent.</p>
            </div>
            <span class="font-mono text-[10px] text-brass">LIVE TRADE GATE</span>
        </div>
        <div class="grid md:grid-cols-5 gap-3">
            @foreach($readiness as $key => $state)
                <div class="border rounded-lg p-4 {{ $state === 'ok' ? 'border-gain/30 bg-gain/5' : 'border-loss/30 bg-loss/5' }}">
                    <div class="flex items-center justify-between gap-2">
                        <span class="font-mono text-[10px] tracking-wide text-muted">{{ strtoupper(str_replace('_', ' ', $key)) }}</span>
                        <span class="font-mono text-[10px] px-2 py-0.5 rounded border {{ $state === 'ok' ? 'border-gain/40 text-gain' : 'border-loss/40 text-loss' }}">
                            {{ strtoupper($state) }}
                        </span>
                    </div>
                </div>
            @endforeach
        </div>
        <div class="grid md:grid-cols-2 gap-3 mt-4 text-xs text-muted">
            <div class="bg-ink/40 rounded p-3 border border-line">Latest signal: {{ $latestSignal?->direction ? strtoupper($latestSignal->direction).' '.$latestSignal->instrument?->symbol : 'none' }}</div>
            <div class="bg-ink/40 rounded p-3 border border-line">Latest training run: {{ $latestTrainingRun?->status ?? 'none' }}{{ $latestTrainingRun?->model?->artifact_uri ? ' · artifact ready' : '' }}</div>
        </div>
    </div>

    <div class="flex items-center justify-between mb-8">
        <h1 class="font-display text-3xl">STETECH control center</h1>
        <form method="POST" action="{{ route('admin.emergency-stop') }}" onsubmit="return confirm('Halt trading for every account? This cannot be undone by mistake.');">
            @csrf
            <button class="border border-loss/50 text-loss px-5 py-2.5 rounded font-mono text-xs tracking-wide hover:bg-loss/10 transition">EMERGENCY STOP ALL</button>
        </form>
    </div>

    <div class="grid grid-cols-2 md:grid-cols-6 gap-4 mb-8">
        @php
            $cards = [
                ['Clients', $stats['clients']], ['Connected MT5', $stats['connected_accounts']],
                ['Active subs', $stats['active_subscriptions']], ['Trades today', $stats['trades_today']],
                ['Open positions', $stats['open_positions']], ['Halted accounts', $stats['halted_accounts']],
            ];
        @endphp
        @foreach ($cards as [$label,$val])
            <div class="bg-panel border border-line rounded-lg p-4">
                <p class="font-mono text-[10px] tracking-wide text-muted mb-2">{{ strtoupper($label) }}</p>
                <p class="font-mono text-xl">{{ $val }}</p>
            </div>
        @endforeach
    </div>

    <div class="bg-panel border border-line rounded-lg overflow-hidden">
        <div class="px-5 py-3 border-b border-line font-mono text-[11px] tracking-[0.15em] text-muted">RECENT TRADES</div>
        <table class="w-full text-sm">
            <thead>
                <tr class="text-left text-muted font-mono text-[11px] border-b border-line">
                    <th class="px-5 py-2 font-normal">Client</th>
                    <th class="px-5 py-2 font-normal">Instrument</th>
                    <th class="px-5 py-2 font-normal">Side</th>
                    <th class="px-5 py-2 font-normal">Mode</th>
                    <th class="px-5 py-2 font-normal">Status</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-line">
                @forelse ($recentTrades as $trade)
                    <tr>
                        <td class="px-5 py-3">{{ $trade->user->name ?? '—' }}</td>
                        <td class="px-5 py-3 font-mono">{{ $trade->instrument->symbol ?? '—' }}</td>
                        <td class="px-5 py-3 font-mono {{ $trade->side === 'buy' ? 'text-gain' : 'text-loss' }}">{{ strtoupper($trade->side) }}</td>
                        <td class="px-5 py-3 font-mono text-muted">{{ strtoupper($trade->mode) }}</td>
                        <td class="px-5 py-3 font-mono text-xs">{{ strtoupper($trade->status) }}</td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="px-5 py-6 text-muted text-center">No trades yet.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

@endsection
