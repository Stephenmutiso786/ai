@extends('layouts.app')
@section('title', 'Request a custom package — STETECH AI')
@section('content')
@include('dashboard._nav')

<div class="max-w-lg mx-auto px-6 py-14">
    <p class="font-mono text-xs tracking-[0.2em] text-brass mb-3">CUSTOM PACKAGE</p>
    <h1 class="font-display text-3xl mb-2">Tell us what you need</h1>
    <p class="text-sm text-muted mb-8 leading-relaxed">
        An admin reviews every request personally and sets a price and weekly run
        allowance for your account specifically — nothing is auto-approved.
    </p>

    @if ($errors->any())
        <div class="mb-6 border border-loss/40 bg-loss/10 text-loss text-sm rounded px-4 py-3">
            <ul class="list-disc list-inside">
                @foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach
            </ul>
        </div>
    @endif

    <form method="POST" action="{{ route('custom-package.store') }}" class="space-y-5">
        @csrf
        <div>
            <label class="font-mono text-xs text-muted block mb-1.5">WHAT DO YOU NEED?</label>
            <textarea name="message" rows="5" placeholder="e.g. I need ~40 runs/week across gold and 3 majors, plus full automation on my HFM account." class="w-full bg-panel border border-line rounded px-3 py-2.5 text-sm focus:border-brass outline-none" required>{{ old('message') }}</textarea>
        </div>
        <div>
            <label class="font-mono text-xs text-muted block mb-1.5">DESIRED RUNS PER WEEK (OPTIONAL)</label>
            <input type="number" name="requested_runs_per_week" min="1" value="{{ old('requested_runs_per_week') }}" placeholder="e.g. 40" class="w-full bg-panel border border-line rounded px-3 py-2.5 text-sm focus:border-brass outline-none">
        </div>
        <button type="submit" class="w-full bg-brass text-ink rounded py-2.5 text-sm font-medium hover:bg-brass/90 transition">Send request</button>
    </form>
</div>

@endsection
