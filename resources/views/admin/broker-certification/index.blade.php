@extends('layouts.app')
@section('title', 'Broker Certification — STETECH AI')
@section('content')
@include('dashboard._nav')

<div class="max-w-7xl mx-auto px-6 py-8">
    @if(session('status'))
        <div class="mb-6 border border-brass/40 bg-brass/10 text-brass text-sm rounded px-4 py-3">{{ session('status') }}</div>
    @endif

    <div class="grid lg:grid-cols-2 gap-6">
        <section class="bg-panel border border-line rounded-lg p-6">
            <p class="font-mono text-xs tracking-[0.15em] text-muted mb-4">BROKER ACCOUNTS</p>
            <div class="space-y-3">
                @forelse($accounts as $account)
                    <div class="border border-line rounded px-4 py-3 flex items-center justify-between gap-4">
                        <div>
                            <p class="font-medium">{{ $account->broker }} · {{ $account->platform }}</p>
                            <p class="text-xs text-muted font-mono">#{{ $account->account_number }} · {{ $account->connection_status }}</p>
                        </div>
                        <form method="POST" action="{{ route('admin.broker-certification.run', $account) }}">
                            @csrf
                            <button class="bg-brass text-ink px-3 py-2 rounded text-xs font-medium">Run certification</button>
                        </form>
                    </div>
                @empty
                    <p class="text-sm text-muted">No broker accounts found.</p>
                @endforelse
            </div>
        </section>

        <section class="bg-panel border border-line rounded-lg p-6">
            <p class="font-mono text-xs tracking-[0.15em] text-muted mb-4">RECENT CERTIFICATIONS</p>
            <div class="space-y-3">
                @forelse($certifications as $certification)
                    <div class="border border-line rounded px-4 py-3">
                        <p class="font-medium">{{ $certification->status }}</p>
                        <p class="text-xs text-muted font-mono">{{ $certification->created_at?->diffForHumans() }}</p>
                    </div>
                @empty
                    <p class="text-sm text-muted">No certifications yet.</p>
                @endforelse
            </div>
        </section>
    </div>

    <section class="mt-6 bg-panel border border-line rounded-lg p-6">
        <p class="font-mono text-xs tracking-[0.15em] text-muted mb-4">OPEN / RETRYING FAILURES</p>
        <div class="space-y-3">
            @forelse($failures as $failure)
                <div class="border border-line rounded px-4 py-3 flex items-center justify-between gap-4">
                    <div>
                        <p class="font-medium">{{ $failure->title ?? 'Execution failure' }}</p>
                        <p class="text-xs text-muted font-mono">{{ $failure->status }} · {{ $failure->created_at?->diffForHumans() }}</p>
                    </div>
                    <form method="POST" action="{{ route('admin.execution-failures.resolve', $failure) }}">
                        @csrf
                        <button class="border border-line px-3 py-2 rounded text-xs">Resolve</button>
                    </form>
                </div>
            @empty
                <p class="text-sm text-muted">No open failures.</p>
            @endforelse
        </div>
    </section>
</div>
@endsection

