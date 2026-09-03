@extends('layouts.app')
@section('title','AI Datasets — STETECH AI')
@section('content')
@include('dashboard._nav')
<div class="max-w-6xl mx-auto px-6 py-8"><h1 class="font-display text-3xl mb-6">AI datasets</h1>@if(session('status'))<div class="mb-5 text-brass">{{session('status')}}</div>@endif
<form method="POST" enctype="multipart/form-data" action="{{route('admin.ai-lab.datasets.store')}}" class="bg-panel border border-line rounded-lg p-5 space-y-4 mb-8">@csrf
<div class="grid md:grid-cols-3 gap-3">
<input name="name" required placeholder="Dataset name" class="bg-ink border border-line rounded px-3 py-2">
<input name="provider" placeholder="Provider" class="bg-ink border border-line rounded px-3 py-2">
<input name="instrument_symbol" placeholder="EURUSD" class="bg-ink border border-line rounded px-3 py-2">
<input name="timeframe" placeholder="H1" class="bg-ink border border-line rounded px-3 py-2">
<input name="storage_uri" placeholder="Optional storage URI" class="bg-ink border border-line rounded px-3 py-2 md:col-span-2">
</div>
<div class="grid lg:grid-cols-2 gap-4">
    <div>
        <label class="block text-sm mb-2">Upload CSV/TXT</label>
        <input type="file" name="upload_file" accept=".csv,.txt" class="w-full bg-ink border border-line rounded px-3 py-2">
        <p class="text-xs text-muted mt-1">Upload a headered OHLCV CSV with columns like timestamp, open, high, low, close, volume.</p>
    </div>
    <div>
        <label class="block text-sm mb-2">Paste OHLCV data</label>
        <textarea name="pasted_data" rows="7" placeholder="timestamp,open,high,low,close,volume&#10;2026-08-27 10:00:00,1.1000,1.1010,1.0990,1.1005,1200" class="w-full bg-ink border border-line rounded px-3 py-2 font-mono text-xs"></textarea>
    </div>
</div>
<button class="bg-brass text-ink rounded px-4 py-2">Register dataset</button></form>
<div class="bg-panel border border-line rounded-lg overflow-hidden"><table class="w-full text-sm"><thead class="text-muted"><tr><th class="p-3 text-left">Name</th><th>Market</th><th>Status</th><th>Rows</th></tr></thead><tbody>@foreach($datasets as $d)<tr class="border-t border-line"><td class="p-3">{{ $d->name }}</td><td>{{ $d->instrument_symbol }} {{ $d->timeframe }}</td><td>{{ strtoupper($d->status) }}</td><td>{{ number_format($d->row_count) }}</td></tr>@endforeach</tbody></table></div></div>@endsection
