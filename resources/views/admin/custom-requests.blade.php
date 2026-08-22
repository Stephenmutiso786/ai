@extends('layouts.app')
@section('title', 'Custom requests — STETECH AI')
@section('content')
@include('dashboard._nav')

<div class="max-w-4xl mx-auto px-6 py-8">
    <h1 class="font-display text-3xl mb-6">Custom package requests</h1>

    @if(session('status'))
        <div class="mb-6 border border-brass/40 bg-brass/10 text-brass text-sm rounded px-4 py-3">{{ session('status') }}</div>
    @endif

    <div class="space-y-5">
        @forelse ($requests as $req)
            <div class="bg-panel border border-line rounded-lg p-5">
                <div class="flex items-center justify-between mb-3">
                    <div>
                        <p class="text-sm font-medium">{{ $req->user->name }} <span class="text-muted font-normal">{{ $req->user->email }}</span></p>
                        <p class="text-xs text-muted font-mono mt-0.5">{{ $req->created_at->diffForHumans() }} · requested {{ $req->requested_runs_per_week ?? 'unspecified' }} runs/week</p>
                    </div>
                    <span class="font-mono text-xs px-2 py-1 rounded border
                        {{ $req->status === 'pending' ? 'border-brass/40 text-brass' : ($req->status === 'approved' ? 'border-gain/40 text-gain' : 'border-loss/40 text-loss') }}">
                        {{ strtoupper($req->status) }}
                    </span>
                </div>
                <p class="text-sm text-muted mb-4 leading-relaxed">{{ $req->message }}</p>

                @if($req->status === 'pending')
                    <form method="POST" action="{{ route('admin.custom-requests.approve', $req) }}" class="flex flex-wrap items-end gap-3 mb-3">
                        @csrf
                        <div>
                            <label class="font-mono text-[11px] text-muted block mb-1">PRICE (USD/WK)</label>
                            <input type="number" name="approved_price_usd_weekly" required min="0" class="w-32 bg-ink border border-line rounded px-2.5 py-2 text-sm font-mono focus:border-brass outline-none">
                        </div>
                        <div>
                            <label class="font-mono text-[11px] text-muted block mb-1">RUNS/WEEK</label>
                            <input type="number" name="approved_runs_per_week" min="1" value="{{ $req->requested_runs_per_week }}" class="w-32 bg-ink border border-line rounded px-2.5 py-2 text-sm font-mono focus:border-brass outline-none">
                        </div>
                        <label class="text-xs text-muted flex items-center gap-1.5 pb-2.5">
                            <input type="checkbox" name="approved_runs_unlimited" value="1" class="accent-brass"> unlimited
                        </label>
                        <button type="submit" class="bg-gain/10 border border-gain/40 text-gain px-4 py-2 rounded text-sm hover:bg-gain/20 transition">Approve</button>
                    </form>
                    <form method="POST" action="{{ route('admin.custom-requests.reject', $req) }}">
                        @csrf
                        <button type="submit" class="border border-loss/40 text-loss px-4 py-2 rounded text-sm hover:bg-loss/10 transition">Reject</button>
                    </form>
                @else
                    <p class="text-xs text-muted font-mono">Reviewed by {{ $req->reviewer?->name }} · {{ $req->reviewed_at?->diffForHumans() }}</p>
                @endif
            </div>
        @empty
            <p class="text-sm text-muted">No requests yet.</p>
        @endforelse
    </div>

    <div class="mt-6">{{ $requests->links() }}</div>
</div>

@endsection
