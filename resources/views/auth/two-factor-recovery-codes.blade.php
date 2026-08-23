@extends('layouts.app')
@section('title', 'Recovery codes — STETECH AI')
@section('content')
@include('dashboard._nav')

<div class="max-w-md mx-auto px-6 py-14">
    <p class="font-mono text-xs tracking-[0.2em] text-brass mb-3">RECOVERY CODES</p>
    <h1 class="font-display text-3xl mb-2">Save these codes now</h1>
    <p class="text-sm text-muted mb-8">Each code can only be used once. Store them somewhere safe - they won’t be shown again.</p>

    <div class="bg-panel border border-line rounded-lg p-4 mb-6 font-mono text-sm space-y-2">
        @foreach($codes as $code)
            <div class="break-all">{{ $code }}</div>
        @endforeach
    </div>

    <a href="{{ route('dashboard') }}" class="block text-center bg-brass text-ink rounded py-2.5 text-sm font-medium hover:bg-brass/90 transition">Continue</a>
</div>
@endsection

