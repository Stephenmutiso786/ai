@extends('layouts.app')
@section('title','Operations — STETECH AI')
@section('content')
@include('dashboard._nav')
<div class="max-w-7xl mx-auto px-6 py-8">
<h1 class="font-display text-3xl mb-2">Production Operations</h1><p class="text-sm text-muted mb-6">Live health, incidents and backup history.</p>
@if(session('status'))<div class="mb-4 border border-brass/40 bg-brass/10 text-brass rounded px-4 py-3">{{session('status')}}</div>@endif
<form method="POST" action="{{route('admin.operations.check')}}" class="mb-6">@csrf<button class="bg-brass text-ink px-4 py-2 rounded">Run health check</button></form>
<div class="grid lg:grid-cols-2 gap-6"><div class="bg-panel border border-line rounded-lg p-5"><h2 class="mb-4">Recent incidents</h2>@forelse($incidents as $i)<div class="border-t border-line py-3"><b>{{strtoupper($i->severity)}} · {{$i->component}}</b><div>{{$i->title}}</div><div class="text-xs text-muted">{{$i->last_seen_at}}</div>@if($i->status==='open')<div class="mt-2 flex gap-2"><form method="POST" action="{{route('admin.operations.ack',$i->id)}}">@csrf<button class="text-xs border border-line px-2 py-1">Acknowledge</button></form><form method="POST" action="{{route('admin.operations.resolve',$i->id)}}">@csrf<button class="text-xs border border-line px-2 py-1">Resolve</button></form></div>@endif</div>@empty<p class="text-muted">No incidents recorded.</p>@endforelse</div>
<div class="bg-panel border border-line rounded-lg p-5"><h2 class="mb-4">Recent health checks</h2>@foreach($checks as $c)<div class="border-t border-line py-2 flex justify-between"><span>{{$c->component}}</span><span class="font-mono text-xs">{{$c->status}} · {{$c->latency_ms}}ms</span></div>@endforeach</div></div>
<div class="bg-panel border border-line rounded-lg p-5 mt-6"><h2 class="mb-4">Backup runs</h2>@forelse($backups as $b)<div class="border-t border-line py-2">{{$b->type}} · {{$b->status}} · {{$b->location ?? 'no location'}} · {{$b->completed_at}}</div>@empty<p class="text-muted">No backup runs recorded yet. Configure and run the production backup script.</p>@endforelse</div></div>
@endsection
