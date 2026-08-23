@extends('layouts.app')
@section('title', 'Plans — STETECH AI')
@section('content')
@include('dashboard._nav')

<div class="max-w-4xl mx-auto px-6 py-8">
    <h1 class="font-display text-3xl mb-2">Plans</h1>
    <p class="text-sm text-muted mb-8">Prices are stored in USD and converted for display everywhere else on the site.</p>

    @if(session('status'))
        <div class="mb-6 border border-brass/40 bg-brass/10 text-brass text-sm rounded px-4 py-3">{{ session('status') }}</div>
    @endif

    <div class="space-y-4">
        @foreach ($plans as $plan)
            <form method="POST" action="{{ route('admin.plans.update', $plan) }}" class="bg-panel border border-line rounded-lg p-5 flex flex-wrap items-end gap-4">
                @csrf
                @method('PUT')
                <div class="w-28">
                    <label class="font-mono text-[11px] text-muted block mb-1">PLAN</label>
                    <p class="text-sm py-2">{{ $plan->name }}</p>
                </div>
                <div class="w-36">
                    <label class="font-mono text-[11px] text-muted block mb-1">PRICE (USD/WK)</label>
                    <input type="number" name="price_usd_weekly" value="{{ $plan->price_usd_weekly }}" placeholder="blank = contact us" {{ $plan->is_demo ? 'disabled' : '' }} class="w-full bg-ink border border-line rounded px-2.5 py-2 text-sm font-mono focus:border-brass outline-none disabled:opacity-40">
                </div>
                <div class="w-36">
                    <label class="font-mono text-[11px] text-muted block mb-1">RUNS/WEEK</label>
                    <input type="number" name="runs_per_week" value="{{ $plan->runs_per_week }}" placeholder="blank = unlimited" {{ $plan->is_demo ? 'disabled' : '' }} class="w-full bg-ink border border-line rounded px-2.5 py-2 text-sm font-mono focus:border-brass outline-none disabled:opacity-40">
                </div>
                <div class="w-40">
                    <label class="font-mono text-[11px] text-muted block mb-1">BROKER LIMIT</label>
                    <input type="number" name="broker_connections_limit" value="{{ $plan->broker_connections_limit }}" placeholder="blank = unlimited" class="w-full bg-ink border border-line rounded px-2.5 py-2 text-sm font-mono focus:border-brass outline-none">
                </div>
                <label class="text-xs text-muted flex items-center gap-1.5 pb-2.5">
                    <input type="checkbox" name="runs_unlimited" value="1" class="accent-brass"> unlimited
                </label>
                <label class="text-xs text-muted flex items-center gap-1.5 pb-2.5">
                    <input type="checkbox" name="is_active" value="1" {{ $plan->is_active ? 'checked' : '' }} class="accent-brass"> active
                </label>
                <button type="submit" class="ml-auto bg-brass text-ink px-4 py-2 rounded text-sm font-medium hover:bg-brass/90 transition">Save</button>
            </form>
        @endforeach
    </div>
</div>

@endsection
