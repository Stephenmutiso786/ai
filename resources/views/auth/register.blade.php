@extends('layouts.app')
@section('title', 'Create Account — STETECH AI')
@section('content')
<div class="min-h-screen flex items-center justify-center px-6 py-12">
    <div class="w-full max-w-md bg-panel border border-line rounded-lg p-8">
        <div class="mb-8">
            <p class="font-mono text-xs tracking-[0.2em] text-brass mb-3">REGISTER</p>
            <h1 class="font-display text-4xl">Create your trading account</h1>
        </div>

        @if($errors->any())
            <div class="mb-5 border border-loss/40 bg-loss/10 text-loss text-sm rounded px-4 py-3">
                {{ $errors->first() }}
            </div>
        @endif

        <div class="mb-6 bg-ink border border-line rounded p-4">
            <p class="font-mono text-[11px] tracking-[0.15em] text-muted mb-1">SELECTED PLAN</p>
            <p class="text-lg">{{ $plan->name }}</p>
            <p class="text-sm text-muted mt-1">
                {{ $plan->automation_allowed ? 'Automation enabled' : 'Signals only' }}
                @if(!is_null($plan->price_usd_weekly))
                    · {{ $plan->price_usd_weekly }} USD / week
                @endif
            </p>
        </div>

        <form method="POST" action="{{ route('register.store') }}" class="space-y-4">
            @csrf
            <input type="hidden" name="plan" value="{{ $plan->slug }}">
            <div>
                <label class="block text-sm text-muted mb-2">Name</label>
                <input name="name" type="text" value="{{ old('name') }}" required class="w-full bg-ink border border-line rounded px-4 py-3 outline-none focus:border-brass">
            </div>
            <div>
                <label class="block text-sm text-muted mb-2">Email</label>
                <input name="email" type="email" value="{{ old('email') }}" required class="w-full bg-ink border border-line rounded px-4 py-3 outline-none focus:border-brass">
            </div>
            <div>
                <label class="block text-sm text-muted mb-2">Password</label>
                <input name="password" type="password" required class="w-full bg-ink border border-line rounded px-4 py-3 outline-none focus:border-brass">
            </div>
            <div>
                <label class="block text-sm text-muted mb-2">Confirm password</label>
                <input name="password_confirmation" type="password" required class="w-full bg-ink border border-line rounded px-4 py-3 outline-none focus:border-brass">
            </div>
            <button class="w-full bg-brass text-ink rounded py-3 font-medium hover:bg-brass/90 transition">Create account</button>
        </form>

        <p class="mt-6 text-sm text-muted">
            Already have an account? <a href="{{ route('login') }}" class="text-brass hover:underline">Sign in</a>
        </p>
    </div>
</div>
@endsection
