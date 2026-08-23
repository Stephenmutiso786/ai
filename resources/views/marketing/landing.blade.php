@extends('layouts.app')
@section('title', 'STETECH AI — Trading Automation, Risk-Controlled')
@section('content')

<!-- Ticker tape -->
<div class="border-b border-line bg-panel overflow-hidden py-2">
    <div class="flex gap-10 font-mono text-xs whitespace-nowrap animate-[ticker_28s_linear_infinite]">
        @php
            $ticker = [
                ['EURUSD','1.0854','+0.12','gain'],['GBPUSD','1.2648','-0.08','loss'],
                ['USDJPY','151.22','+0.31','gain'],['XAUUSD','2411.40','+0.64','gain'],
                ['GBPJPY','191.28','-0.19','loss'],['AUDUSD','0.6521','+0.05','gain'],
                ['EURUSD','1.0854','+0.12','gain'],['GBPUSD','1.2648','-0.08','loss'],
                ['USDJPY','151.22','+0.31','gain'],['XAUUSD','2411.40','+0.64','gain'],
                ['GBPJPY','191.28','-0.19','loss'],['AUDUSD','0.6521','+0.05','gain'],
            ];
        @endphp
        @foreach ($ticker as [$sym,$px,$chg,$dir])
            <span class="text-muted">{{ $sym }} <span class="text-slate-200">{{ $px }}</span> <span class="text-{{ $dir }}">{{ $chg }}%</span></span>
        @endforeach
    </div>
</div>
<style>@keyframes ticker { from { transform: translateX(0); } to { transform: translateX(-50%); } }</style>

<!-- Nav -->
<nav class="max-w-7xl mx-auto flex items-center justify-between px-6 py-6">
    <div class="flex items-baseline gap-2">
        <span class="font-display italic text-2xl">STETECH</span>
        <span class="font-mono text-[10px] tracking-[0.2em] text-brass">AI TRADER</span>
    </div>
    <div class="hidden md:flex items-center gap-8 text-sm text-muted">
        <a href="#pipeline" class="hover:text-slate-100">How it works</a>
        <a href="#risk" class="hover:text-slate-100">Risk controls</a>
        <a href="{{ route('pricing') }}" class="hover:text-slate-100">Pricing</a>
        <a href="#compliance" class="hover:text-slate-100">Compliance</a>
    </div>
    <div class="flex items-center gap-4">
        @include('partials.currency-switcher')
        @auth
            <a href="{{ route('dashboard') }}" class="text-sm font-medium bg-brass text-ink px-4 py-2 rounded hover:bg-brass/90 transition">Open dashboard</a>
        @else
            <a href="{{ route('login') }}" class="text-sm font-medium border border-line px-4 py-2 rounded hover:border-brass/60 transition">Login</a>
            <a href="{{ route('register') }}" class="text-sm font-medium bg-brass text-ink px-4 py-2 rounded hover:bg-brass/90 transition">Create account</a>
        @endauth
    </div>
</nav>

<!-- Hero -->
<header class="max-w-7xl mx-auto px-6 pt-10 pb-24 grid lg:grid-cols-5 gap-12 items-center">
    <div class="lg:col-span-3">
        <p class="font-mono text-xs tracking-[0.2em] text-brass mb-5">STETECH LIMITED — TRADING AUTOMATION TOOL</p>
        <h1 class="font-display text-5xl md:text-6xl leading-[1.05] mb-6">
            A trading automation<br> layer, not a broker<span class="text-brass">.</span>
        </h1>
        <p class="text-lg text-muted max-w-xl leading-relaxed mb-8">
            STETECH AI reads the market across your instrument universe, proposes a position,
            and hands the decision to a risk engine that can say no. Your funds stay with your
            own broker. We connect to the account you already have — we don't hold it.
        </p>
        <div class="flex flex-wrap gap-4">
            @auth
                <a href="{{ route('dashboard') }}" class="bg-brass text-ink px-6 py-3 rounded font-medium hover:bg-brass/90 transition">Open the dashboard</a>
            @else
                <a href="{{ route('register') }}" class="bg-brass text-ink px-6 py-3 rounded font-medium hover:bg-brass/90 transition">Create account</a>
                <a href="{{ route('login') }}" class="border border-line px-6 py-3 rounded font-medium text-slate-200 hover:border-brass/60 transition">Sign in</a>
            @endauth
            <a href="#pipeline" class="border border-line px-6 py-3 rounded font-medium text-slate-200 hover:border-brass/60 transition">See the pipeline</a>
        </div>
    </div>

    <!-- Signature: live-looking AI market status panel -->
    <div class="lg:col-span-2 bg-panel border border-line rounded-lg overflow-hidden">
        <div class="flex items-center justify-between px-5 py-3 border-b border-line">
            <span class="font-mono text-[11px] tracking-[0.15em] text-muted">AI MARKET STATUS</span>
            <span class="flex items-center gap-1.5 font-mono text-[11px] text-gain"><span class="w-1.5 h-1.5 rounded-full bg-gain animate-pulse"></span> LIVE</span>
        </div>
        <div class="divide-y divide-line">
            @php
                $rows = [
                    ['XAUUSD','BUY',78,'gain'],['EURUSD','WAIT',54,'muted'],
                    ['GBPUSD','SELL',71,'loss'],['GBPJPY','BUY',66,'gain'],
                    ['USDJPY','SELL',82,'loss'],
                ];
            @endphp
            @foreach ($rows as [$sym,$dir,$conf,$tone])
                <div class="flex items-center justify-between px-5 py-3">
                    <span class="font-mono text-sm">{{ $sym }}</span>
                    <span class="font-mono text-xs px-2 py-0.5 rounded border
                        {{ $dir === 'BUY' ? 'border-gain/40 text-gain' : ($dir === 'SELL' ? 'border-loss/40 text-loss' : 'border-line text-muted') }}">{{ $dir }}</span>
                    <div class="w-24 h-1.5 bg-line rounded-full overflow-hidden">
                        <div class="h-full bg-{{ $tone === 'muted' ? 'muted' : $tone }}" style="width: {{ $conf }}%"></div>
                    </div>
                    <span class="font-mono text-xs text-muted w-10 text-right">{{ $conf }}%</span>
                </div>
            @endforeach
        </div>
        <div class="px-5 py-3 bg-ink/60 font-mono text-[10px] text-muted">Illustrative sample output — not investment advice.</div>
    </div>
</header>

<!-- Pipeline -->
<section id="pipeline" class="max-w-7xl mx-auto px-6 py-20 border-t border-line">
    <p class="font-mono text-xs tracking-[0.2em] text-brass mb-3">THE PIPELINE</p>
    <h2 class="font-display text-4xl mb-14 max-w-2xl">The AI proposes. It never has the last word.</h2>

    <div class="grid md:grid-cols-5 gap-6">
        @php
            $stages = [
                ['01','Market data','Price, volatility and session data across your instrument universe, continuously refreshed.'],
                ['02','AI signal engine','Technical + ML models produce a direction, confidence score and reasoning — never a certainty.'],
                ['03','Risk engine','Checks exposure, daily/weekly loss limits, open positions and margin. It can reject any signal, with a stated reason.'],
                ['04','Execution engine','Only an approved decision reaches an order. Paper mode by default until a strategy has proven itself.'],
                ['05','Your broker','Trades execute in the account you already control — STETECH never custodies client funds.'],
            ];
        @endphp
        @foreach ($stages as [$n,$title,$body])
            <div class="border-t-2 border-brass/60 pt-4">
                <span class="font-mono text-xs text-muted">{{ $n }}</span>
                <h3 class="font-medium text-lg mt-2 mb-2">{{ $title }}</h3>
                <p class="text-sm text-muted leading-relaxed">{{ $body }}</p>
            </div>
        @endforeach
    </div>
</section>

<!-- Risk -->
<section id="risk" class="bg-panel border-y border-line">
    <div class="max-w-7xl mx-auto px-6 py-20 grid lg:grid-cols-2 gap-14 items-center">
        <div>
            <p class="font-mono text-xs tracking-[0.2em] text-brass mb-3">RISK, NOT PREDICTION</p>
            <h2 class="font-display text-4xl mb-6">The risk engine is the product.</h2>
            <p class="text-muted leading-relaxed mb-6 max-w-lg">
                Every account carries hard limits the AI cannot override. When a limit is hit,
                trading halts automatically — for that instrument, or across the whole account.
                You can also stop everything by hand, instantly.
            </p>
            <dl class="grid grid-cols-2 gap-5 font-mono text-sm max-w-md">
                <div><dt class="text-muted text-xs mb-1">MAX RISK / TRADE</dt><dd>0.5%</dd></div>
                <div><dt class="text-muted text-xs mb-1">MAX DAILY LOSS</dt><dd>2.0%</dd></div>
                <div><dt class="text-muted text-xs mb-1">MAX OPEN POSITIONS</dt><dd>3</dd></div>
                <div><dt class="text-muted text-xs mb-1">MAX EXPOSURE</dt><dd>10%</dd></div>
            </dl>
        </div>
        <div class="bg-ink border border-line rounded-lg p-6">
            <div class="flex items-center justify-between mb-5">
                <span class="font-mono text-xs tracking-[0.15em] text-muted">CONTROL CENTER</span>
                <span class="font-mono text-[11px] text-gain">● ACTIVE</span>
            </div>
            <div class="space-y-2 font-mono text-sm mb-6">
                <div class="flex justify-between"><span class="text-muted">EURUSD</span><span class="text-gain">● enabled</span></div>
                <div class="flex justify-between"><span class="text-muted">GBPUSD</span><span class="text-gain">● enabled</span></div>
                <div class="flex justify-between"><span class="text-muted">GBPJPY</span><span class="text-loss">● halted</span></div>
                <div class="flex justify-between"><span class="text-muted">XAUUSD</span><span class="text-brass">● watch</span></div>
            </div>
            <button disabled class="w-full border border-loss/50 text-loss py-2.5 rounded font-mono text-xs tracking-wide cursor-not-allowed opacity-80">EMERGENCY STOP ALL — sign in to use</button>
        </div>
    </div>
</section>

<!-- Pricing teaser -->
<section class="max-w-7xl mx-auto px-6 py-20">
    <p class="font-mono text-xs tracking-[0.2em] text-brass mb-3">PLANS</p>
    <h2 class="font-display text-4xl mb-14 max-w-2xl">Plans unlock capacity, not bigger promises.</h2>
    <div class="grid md:grid-cols-4 gap-5">
        @php
            $converter = app(\App\Services\Currency\CurrencyConverter::class);
            $tiers = [
                ['Basic', 9, false, '6 runs/week', 'Signals only'],
                ['Standard', 15, false, '12 runs/week', 'Automation enabled'],
                ['Pro', null, false, 'Unlimited runs/week', 'Automation enabled'],
            ];
        @endphp
        @foreach ($tiers as [$name,$priceUsd,$isDemo,$runsLabel,$auto])
            <div class="border rounded-lg p-6 transition border-line hover:border-brass/50">
                <h3 class="font-medium text-lg mb-1">{{ $name }}</h3>
                <p class="font-mono text-2xl mb-4">
                    @if(is_null($priceUsd)) Contact us
                    @else {{ $converter->format($priceUsd, $currentCurrency) }}<span class="text-muted text-sm">/week</span>
                    @endif
                </p>
                <ul class="text-sm text-muted space-y-2">
                    <li>{{ $runsLabel }}</li><li>{{ $auto }}</li>
                </ul>
            </div>
        @endforeach
    </div>
    <p class="text-xs text-muted mt-6">
        Prices shown in {{ $currentCurrency }}, converted from a USD base rate — switch currency above.
        Need something in between? <a href="{{ route('custom-package.create') }}" class="text-brass hover:underline">Request a custom package</a>.
    </p>
</section>

<!-- Compliance -->
<section id="compliance" class="bg-panel border-t border-line">
    <div class="max-w-7xl mx-auto px-6 py-16 grid md:grid-cols-3 gap-8">
        <div>
            <h3 class="font-medium mb-2">Not a broker</h3>
            <p class="text-sm text-muted leading-relaxed">Client funds stay with the client's own regulated broker. STETECH AI connects to that account; it does not custody money.</p>
        </div>
        <div>
            <h3 class="font-medium mb-2">Paper-first by default</h3>
            <p class="text-sm text-muted leading-relaxed">New strategies run in paper mode until they have a backtested and out-of-sample track record before any live execution is enabled.</p>
        </div>
        <div>
            <h3 class="font-medium mb-2">Full audit trail</h3>
            <p class="text-sm text-muted leading-relaxed">Every signal, risk-engine decision, and order is logged — including why a trade was rejected, not just why it was placed.</p>
        </div>
    </div>
</section>

<footer class="max-w-7xl mx-auto px-6 py-10 flex flex-wrap items-center justify-between gap-4 text-xs text-muted">
    <span>© {{ date('Y') }} STETECH LIMITED</span>
    <span>Trading involves risk of loss. Past performance does not guarantee future results.</span>
</footer>

@endsection
