@extends('layouts.app')
@section('title', 'Two-factor — STETECH AI')
@section('content')
@include('dashboard._nav')

<div class="max-w-md mx-auto px-6 py-14">
    <p class="font-mono text-xs tracking-[0.2em] text-brass mb-3">SECURITY</p>
    <h1 class="font-display text-3xl mb-2">Two-factor authentication</h1>
    <p class="text-sm text-muted mb-8">Two-factor is enabled on your account. You can reconfigure or disable it here.</p>

    @if(session('status'))
        <div class="mb-6 border border-brass/40 bg-brass/10 text-brass text-sm rounded px-4 py-3">{{ session('status') }}</div>
    @endif

    <div class="space-y-4">
        <a href="{{ route('two-factor.show') }}" class="block text-center bg-brass text-ink rounded py-2.5 text-sm font-medium hover:bg-brass/90 transition">View setup</a>
        <form method="POST" action="{{ route('two-factor.disable') }}">
            @csrf
            <div class="mb-3">
                <label class="font-mono text-xs text-muted block mb-1.5">CONFIRM PASSWORD</label>
                <input type="password" name="password" class="w-full bg-panel border border-line rounded px-3 py-2.5 text-sm focus:border-brass outline-none" required>
            </div>
            <button class="w-full border border-loss/50 text-loss rounded py-2.5 text-sm font-medium">Disable 2FA</button>
        </form>
    </div>
</div>
@endsection

