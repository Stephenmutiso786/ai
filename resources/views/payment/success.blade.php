@extends('layouts.app')
@section('title','Payment successful — STETECH AI')
@section('content')
<div class="max-w-lg mx-auto px-6 py-16">
    <div class="bg-panel border border-line rounded-lg p-6">
        <p class="font-mono text-xs tracking-[0.2em] text-brass mb-3">PAYMENT</p>
        <h1 class="font-display text-3xl mb-3">Subscription activated</h1>
        <p class="text-muted mb-6">Your subscription for {{ $subscription->plan?->name ?? 'the selected plan' }} is now active.</p>
        <a href="{{ route('dashboard') }}" class="inline-block bg-brass text-ink px-5 py-2.5 rounded font-medium">Go to dashboard</a>
    </div>
</div>
@endsection

