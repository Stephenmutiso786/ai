@extends('layouts.app')
@section('title', 'Set up two-factor — STETECH AI')
@section('content')
@include('dashboard._nav')

<div class="max-w-md mx-auto px-6 py-14">
    <p class="font-mono text-xs tracking-[0.2em] text-brass mb-3">SECURITY</p>
    <h1 class="font-display text-3xl mb-2">Set up two-factor authentication</h1>
    <p class="text-sm text-muted mb-8">Scan this with Google Authenticator, Authy, or any TOTP app, then enter the 6-digit code it shows.</p>

    @if ($errors->any())
        <div class="mb-6 border border-loss/40 bg-loss/10 text-loss text-sm rounded px-4 py-3">{{ $errors->first() }}</div>
    @endif

    <div id="qr" class="bg-white p-4 rounded-lg inline-block mb-6"></div>
    <p class="font-mono text-xs text-muted mb-8 break-all">Can't scan? Enter manually: <span class="text-slate-200">{{ $secret }}</span></p>

    <form method="POST" action="{{ route('two-factor.confirm') }}" class="space-y-5">
        @csrf
        <div>
            <label class="font-mono text-xs text-muted block mb-1.5">6-DIGIT CODE</label>
            <input name="code" autofocus class="w-full bg-panel border border-line rounded px-3 py-2.5 text-sm font-mono tracking-widest focus:border-brass outline-none" required>
        </div>
        <button type="submit" class="w-full bg-brass text-ink rounded py-2.5 text-sm font-medium hover:bg-brass/90 transition">Confirm and enable</button>
    </form>
</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
<script>
    new QRCode(document.getElementById("qr"), {
        text: @json($uri),
        width: 200,
        height: 200,
    });
</script>

@endsection

