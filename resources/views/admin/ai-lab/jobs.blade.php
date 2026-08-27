@extends('layouts.app')
@section('title','AI Jobs — STETECH AI')
@section('content')
@include('dashboard._nav')
<div class="max-w-7xl mx-auto px-6 py-8">
<div class="flex items-center justify-between mb-6">
    <div>
        <h1 class="font-display text-3xl">AI Training Jobs</h1>
        <p class="text-sm text-muted mt-1">Queue, scheduler, and training visibility for production scaling.</p>
    </div>
    <a href="{{ route('admin.ai-lab.index') }}" class="border border-line px-4 py-2 rounded text-sm">Back to AI Lab</a>
</div>

<div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-8">
    @foreach (['queued' => 'Queued', 'running' => 'Running', 'completed' => 'Completed', 'failed' => 'Failed'] as $key => $label)
        <div class="bg-panel border border-line rounded-lg p-4">
            <p class="font-mono text-[10px] tracking-wide text-muted mb-2">{{ strtoupper($label) }}</p>
            <p class="font-mono text-2xl">{{ $trainingCounts[$key] ?? 0 }}</p>
        </div>
    @endforeach
</div>

<div class="bg-panel border border-line rounded-lg p-5 mb-8">
    <p class="font-mono text-[11px] tracking-[0.15em] text-muted mb-4">READINESS</p>
    <div class="grid md:grid-cols-3 gap-4 text-sm">
        @php
            $checks = [
                ['label' => 'Queue workers', 'status' => ($trainingCounts['queued'] + $trainingCounts['running']) > 0 ? 'ok' : 'warn', 'detail' => 'Active jobs imply the queue is processing.'],
                ['label' => 'Scheduler', 'status' => $latestHealthChecks->where('component', 'ai_service')->first() ? 'ok' : 'warn', 'detail' => 'Production health checks should keep updating this list.'],
                ['label' => 'Training artifact', 'status' => $runs->first()?->model?->artifact_uri ? 'ok' : 'warn', 'detail' => 'A trained model artifact is needed for live signals.'],
            ];
        @endphp
        @foreach($checks as $check)
            <div class="border rounded-lg p-4 {{ $check['status'] === 'ok' ? 'border-gain/30 bg-gain/5' : 'border-loss/30 bg-loss/5' }}">
                <div class="flex items-center justify-between">
                    <span class="font-medium">{{ $check['label'] }}</span>
                    <span class="font-mono text-[10px] px-2 py-0.5 rounded border {{ $check['status'] === 'ok' ? 'border-gain/40 text-gain' : 'border-loss/40 text-loss' }}">{{ strtoupper($check['status']) }}</span>
                </div>
                <p class="text-xs text-muted mt-2">{{ $check['detail'] }}</p>
            </div>
        @endforeach
    </div>
</div>

<div class="bg-panel border border-line rounded-lg overflow-hidden mb-8">
    <div class="px-5 py-3 border-b border-line font-mono text-[11px] tracking-[0.15em] text-muted">RECENT TRAINING RUNS</div>
    <table class="w-full text-sm">
        <thead class="text-left text-muted font-mono text-[11px] border-b border-line">
            <tr>
                <th class="px-5 py-2 font-normal">Model</th>
                <th class="px-5 py-2 font-normal">Dataset</th>
                <th class="px-5 py-2 font-normal">Status</th>
                <th class="px-5 py-2 font-normal">Started</th>
                <th class="px-5 py-2 font-normal">Finished</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-line">
            @forelse($runs as $run)
                <tr>
                    <td class="px-5 py-3">{{ $run->model?->name ?? '—' }} {{ $run->model?->version ?? '' }}</td>
                    <td class="px-5 py-3">{{ $run->dataset?->name ?? 'Local fallback' }}</td>
                    <td class="px-5 py-3 font-mono">{{ strtoupper($run->status) }}</td>
                    <td class="px-5 py-3 text-muted">{{ $run->started_at?->diffForHumans() ?? '—' }}</td>
                    <td class="px-5 py-3 text-muted">{{ $run->finished_at?->diffForHumans() ?? '—' }}</td>
                </tr>
            @empty
                <tr><td colspan="5" class="px-5 py-6 text-center text-muted">No training jobs yet.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>

<div class="bg-panel border border-line rounded-lg overflow-hidden">
    <div class="px-5 py-3 border-b border-line font-mono text-[11px] tracking-[0.15em] text-muted">LATEST HEALTH CHECKS</div>
    <div class="divide-y divide-line">
        @forelse($latestHealthChecks as $check)
            <div class="px-5 py-3 flex items-center justify-between text-sm">
                <span>{{ $check->component }}</span>
                <span class="font-mono text-xs">{{ strtoupper($check->status) }} · {{ $check->latency_ms }}ms</span>
            </div>
        @empty
            <div class="px-5 py-6 text-muted">No health checks yet.</div>
        @endforelse
    </div>
</div>
</div>
@endsection
