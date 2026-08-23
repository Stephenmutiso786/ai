@extends('layouts.app')
@section('title','Payment — STETECH AI')
@section('content')
<div class="max-w-xl mx-auto px-6 py-10">
    <h1 class="font-display text-3xl mb-2">Activate subscription</h1>
    <p class="text-muted mb-8">Choose a secure payment method. Your subscription is activated only after provider confirmation.</p>

    @if(session('status'))
        <div class="mb-4 border border-brass/40 p-3">{{ session('status') }}</div>
    @endif

    <form method="POST" action="{{ route('payments.checkout') }}" class="space-y-5 bg-panel border border-line p-6 rounded-lg">
        @csrf
        <input type="hidden" name="plan" value="{{ $plan->id }}">
        <div>
            <div class="text-xl">{{ $plan->name }}</div>
            <div class="text-muted">${{ number_format($plan->price_usd_weekly/100,2) }} per week</div>
        </div>
        <label class="block">
            <span>Payment method</span>
            <select name="provider" id="provider" class="w-full mt-1">
                <option value="mpesa">M-Pesa</option>
                <option value="stripe">Card via Stripe</option>
            </select>
        </label>
        <label class="block" id="phone-wrap">
            <span>M-Pesa phone number</span>
            <input name="phone" placeholder="2547XXXXXXXX or 07XXXXXXXX" class="w-full mt-1">
        </label>
        <button class="bg-brass text-ink px-5 py-3 rounded">Continue securely</button>
    </form>
</div>

<script>
document.getElementById('provider').addEventListener('change',e=>document.getElementById('phone-wrap').style.display=e.target.value==='mpesa'?'block':'none');
</script>
@endsection

