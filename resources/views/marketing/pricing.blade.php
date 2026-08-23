@extends('layouts.app')
@section('title', 'Pricing — STETECH AI')
@section('content')

<nav class="max-w-7xl mx-auto flex items-center justify-between px-6 py-6">
    <a href="{{ route('home') }}" class="flex items-baseline gap-2">
        <span class="font-display italic text-2xl">STETECH</span>
        <span class="font-mono text-[10px] tracking-[0.2em] text-brass">AI TRADER</span>
    </a>
    <div class="flex items-center gap-4">
        @include('partials.currency-switcher')
        <a href="{{ route('dashboard') }}" class="text-sm font-medium bg-brass text-ink px-4 py-2 rounded hover:bg-brass/90 transition">Open dashboard</a>
    </div>
</nav>

<section class="max-w-7xl mx-auto px-6 py-16">
    <p class="font-mono text-xs tracking-[0.2em] text-brass mb-3">PLANS</p>
    <h1 class="font-display text-5xl mb-4">Runs per week, not looser risk limits.</h1>
    <p class="text-muted max-w-2xl mb-14 leading-relaxed">
        Every tier runs under the same hard-coded risk engine. Higher plans unlock more
        AI runs per week and automation — not bigger promises.
    </p>

    @php $converter = app(\App\Services\Currency\CurrencyConverter::class); @endphp
    <div class="grid md:grid-cols-4 gap-5">
        @foreach ($plans as $plan)
            <div class="border rounded-lg p-6 flex flex-col transition border-line hover:border-brass/50">
                <h3 class="font-medium text-lg mb-1">{{ $plan->name }}</h3>
                <p class="font-mono text-2xl mb-6">
                    @if(is_null($plan->price_usd_weekly))
                        Contact us
                    @else
                        {{ $converter->format($plan->price_usd_weekly, $currentCurrency) }}<span class="text-muted text-sm">/week</span>
                    @endif
                </p>
                <ul class="text-sm text-muted space-y-3 mb-8 flex-1">
                    <li class="flex justify-between">
                        <span>Runs</span>
                        <span class="text-slate-200">
                            @if(is_null($plan->runs_per_week)) Unlimited
                            @else {{ $plan->runs_per_week }}/week
                            @endif
                        </span>
                    </li>
                    <li class="flex justify-between"><span>Automation</span><span class="text-slate-200">{{ $plan->automation_allowed ? 'Yes' : 'Signals only' }}</span></li>
                </ul>
                @if(auth()->check())
                    <a href="{{ route('payments.show', $plan) }}" class="text-center border border-line rounded py-2.5 text-sm hover:border-brass/60 hover:text-brass transition">Choose {{ $plan->name }}</a>
                @else
                    <a href="{{ route('register', ['plan' => $plan->slug]) }}" class="text-center border border-line rounded py-2.5 text-sm hover:border-brass/60 hover:text-brass transition">Create account with {{ $plan->name }}</a>
                @endif
            </div>
        @endforeach

        <div class="border border-dashed border-line rounded-lg p-6 flex flex-col">
            <h3 class="font-medium text-lg mb-1">Custom</h3>
            <p class="font-mono text-2xl mb-6">You set the terms</p>
            <ul class="text-sm text-muted space-y-3 mb-8 flex-1">
                <li>Tell us the runs/week and features you need</li>
                <li>An admin reviews and prices it for your account</li>
                <li>No public price — negotiated per request</li>
            </ul>
            <a href="{{ route('custom-package.create') }}" class="text-center bg-brass text-ink rounded py-2.5 text-sm font-medium hover:bg-brass/90 transition">Request a custom package</a>
        </div>
    </div>

    <p class="text-xs text-muted mt-8">Prices shown in {{ $currentCurrency }}, converted from a USD base rate — detected from your location, or switch it above.</p>
</section>

@endsection
