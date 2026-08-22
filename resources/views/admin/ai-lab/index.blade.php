@extends('layouts.app')
@section('title','AI Lab — STETECH AI')
@section('content')
@include('dashboard._nav')
<div class="max-w-7xl mx-auto px-6 py-8">
<h1 class="font-display text-3xl">STETECH AI LAB</h1><p class="text-muted text-sm mt-2 mb-8">Production model governance: train, validate, paper test, shadow and deploy with auditability.</p>
@if(session('status'))<div class="mb-6 border border-brass/40 bg-brass/10 text-brass text-sm rounded px-4 py-3">{{ session('status') }}</div>@endif
<div class="grid md:grid-cols-4 gap-4 mb-8">@foreach(['Models'=>$stats['models'],'Live'=>$stats['live'],'Training'=>$stats['training'],'Completed backtests'=>$stats['backtests']] as $label=>$value)<div class="bg-panel border border-line rounded-lg p-5"><div class="text-xs text-muted">{{ $label }}</div><div class="text-3xl font-mono mt-2">{{ $value }}</div></div>@endforeach</div>
<div class="flex gap-3 mb-8"><a class="bg-brass text-ink px-4 py-2 rounded text-sm" href="{{ route('admin.ai-lab.models') }}">Manage models</a><a class="border border-line px-4 py-2 rounded text-sm" href="{{ route('admin.ai-lab.datasets') }}">Datasets</a></div>
<div class="grid lg:grid-cols-2 gap-6">
<div class="bg-panel border border-line rounded-lg p-5"><h2 class="font-display text-xl mb-4">Recent training runs</h2>@forelse($runs as $run)<div class="border-t border-line py-3 text-sm flex justify-between"><span>{{ $run->model?->name }} {{ $run->model?->version }} @if($run->dataset) · {{ $run->dataset->name }} @endif</span><span class="font-mono text-brass">{{ strtoupper($run->status) }}</span></div>@empty<p class="text-muted text-sm">No training runs yet.</p>@endforelse</div>
<div class="bg-panel border border-line rounded-lg p-5"><h2 class="font-display text-xl mb-4">Recent backtests</h2>@forelse($backtests as $b)<div class="border-t border-line py-3 text-sm flex justify-between"><span>{{ $b->model?->name }} · {{ $b->instrument_symbol }} {{ $b->timeframe }}</span><span class="font-mono text-brass">{{ strtoupper($b->status) }}</span></div>@empty<p class="text-muted text-sm">No backtests yet.</p>@endforelse</div>
</div></div>@endsection
