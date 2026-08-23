@extends('layouts.app')
@section('title', 'Verify — STETECH AI')
@section('content')

<div class="max-w-md mx-auto px-6 py-14">
    <p class="font-mono text-xs tracking-[0.2em] text-brass mb-3">SECURITY CHECK</p>
    <h1 class="font-display text-3xl mb-2">Enter your code</h1>
    <p class="text-sm text-muted mb-8">Open your authenticator app, or use one of your recovery codes.</p>

    @if ($errors->any())
        <div class="mb-6 border border-loss/40 bg-loss/10 text-loss text-sm rounded px-4 py-3">{{ $errors->first() }}</div>
    @endif

    <form method="POST" action="{{ route('two-factor.verify') }}" class="space-y-5">
        @csrf
        <div>
            <label class="font-mono text-xs text-muted block mb-1.5">6-DIGIT CODE OR RECOVERY CODE</label>
            <input name="code" autofocus class="w-full bg-panel border border-line rounded px-3 py-2.5 text-sm font-mono tracking-widest focus:border-brass outline-none" required>
        </div>
        <button type="submit" class="w-full bg-brass text-ink rounded py-2.5 text-sm font-medium hover:bg-brass/90 transition">Verify</button>
    </form>
</div>

@endsection

