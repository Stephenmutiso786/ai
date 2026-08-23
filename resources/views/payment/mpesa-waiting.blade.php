@extends('layouts.app')
@section('title','Waiting for M-Pesa — STETECH AI')
@section('content')
<div class="max-w-lg mx-auto px-6 py-16">
    <div class="bg-panel border border-line rounded-lg p-6">
        <p class="font-mono text-xs tracking-[0.2em] text-brass mb-3">PAYMENT</p>
        <h1 class="font-display text-3xl mb-3">Check your phone</h1>
        <p class="text-muted mb-6">We sent an M-Pesa prompt for {{ $subscription->plan?->name ?? 'your subscription' }}. Complete it on your phone, then refresh this page.</p>
        <div id="status" class="text-sm text-muted font-mono">Waiting for confirmation...</div>
    </div>
</div>

<script>
setInterval(async () => {
    try {
        const res = await fetch(@json(route('payment.mpesa.status', $subscription)));
        const data = await res.json();
        document.getElementById('status').textContent = 'Current status: ' + data.status;
        if (data.status === 'active') window.location = @json(route('payment.success', $subscription));
    } catch (e) {}
}, 5000);
</script>
@endsection

