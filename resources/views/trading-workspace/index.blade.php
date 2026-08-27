@extends('layouts.app')
@section('title', 'Trading Workspace — STETECH AI')
@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 py-6 sm:py-8 space-y-6">
    <div class="flex flex-col lg:flex-row lg:items-end lg:justify-between gap-4">
        <div>
            <p class="font-mono text-xs tracking-[0.2em] text-brass mb-2">LIVE TRADING VIEW</p>
            <h1 class="font-display text-3xl">Trading Workspace</h1>
            <p class="text-sm text-muted mt-2">Official TradingView widget with real market data, AI signal overlays, and broker state.</p>
        </div>
        <div class="flex flex-wrap items-center gap-3">
            <label class="text-xs font-mono text-muted">SYMBOL</label>
            <select id="symbol" class="bg-panel border border-line rounded px-3 py-2 text-sm">
                @foreach($instruments as $i)
                    <option value="{{ $i->symbol }}" @selected($i->symbol===$symbol)>{{ $i->symbol }}</option>
                @endforeach
            </select>
            <div class="flex items-center gap-2">
                <button data-tf="15" class="tf-btn border border-line rounded px-3 py-2 text-xs">15m</button>
                <button data-tf="60" class="tf-btn border border-brass/50 text-brass rounded px-3 py-2 text-xs">1H</button>
                <button data-tf="240" class="tf-btn border border-line rounded px-3 py-2 text-xs">4H</button>
                <button data-tf="D" class="tf-btn border border-line rounded px-3 py-2 text-xs">1D</button>
            </div>
            <div id="health-chip" class="text-xs font-mono px-3 py-2 rounded border border-line text-muted">Checking feed…</div>
        </div>
    </div>

    <div class="grid lg:grid-cols-[1.1fr_1.7fr_0.9fr] gap-6">
        <div class="space-y-4">
            <div class="bg-panel border border-line rounded-lg p-5">
                <p class="font-mono text-[11px] tracking-[0.15em] text-muted mb-3">WATCHLIST</p>
                <div class="divide-y divide-line">
                    @foreach($instruments->take(12) as $item)
                        <button type="button" data-symbol="{{ $item->symbol }}" class="watch-item w-full flex items-center justify-between py-2 text-left text-sm hover:text-brass">
                            <span class="font-mono">{{ $item->symbol }}</span>
                            <span class="text-xs text-muted">Open</span>
                        </button>
                    @endforeach
                </div>
            </div>
            <div class="bg-panel border border-line rounded-lg p-5">
                <p class="font-mono text-[11px] tracking-[0.15em] text-muted mb-3">WIDGET STATUS</p>
                <div id="widget-status" class="text-sm text-muted">Loading TradingView widget…</div>
            </div>
        </div>

        <div class="bg-panel border border-line rounded-lg overflow-hidden">
            <div class="flex items-center justify-between px-5 py-3 border-b border-line">
                <div>
                    <p class="font-mono text-[11px] tracking-[0.15em] text-muted">TRADINGVIEW CHART</p>
                    <p id="chart-meta" class="text-xs text-muted mt-1">Loading TradingView widget…</p>
                </div>
                <div class="flex items-center gap-2">
                    <span class="font-mono text-[11px] text-gain">Official widget</span>
                    <span class="font-mono text-[11px] text-muted">|</span>
                    <span class="font-mono text-[11px] text-gain">Real market overlays</span>
                </div>
            </div>
            <div id="tv-chart" style="height:560px;"></div>
        </div>

        <div class="space-y-4">
            <div class="bg-panel border border-line rounded-lg p-5">
                <p class="font-mono text-[11px] tracking-[0.15em] text-muted mb-3">AI SIGNAL</p>
                <div id="signal-card" class="text-sm text-muted">Loading signal…</div>
            </div>

            <div class="bg-panel border border-line rounded-lg p-5">
                <p class="font-mono text-[11px] tracking-[0.15em] text-muted mb-3">MARKET ANALYSIS</p>
                <div id="analysis-card" class="text-sm text-muted">Loading analysis…</div>
            </div>

            <div class="bg-panel border border-line rounded-lg p-5">
                <p class="font-mono text-[11px] tracking-[0.15em] text-muted mb-3">BROKER STATE</p>
                <div id="broker-card" class="text-sm text-muted">Loading broker snapshot…</div>
            </div>

            <div class="bg-panel border border-line rounded-lg p-5">
                <p class="font-mono text-[11px] tracking-[0.15em] text-muted mb-3">OPEN POSITIONS</p>
                <div id="positions" class="text-sm text-muted">Loading positions…</div>
            </div>

            <div class="bg-panel border border-line rounded-lg p-5">
                <p class="font-mono text-[11px] tracking-[0.15em] text-muted mb-3">PERFORMANCE</p>
                <div id="performance" class="text-2xl font-mono">—</div>
                <div id="performance-meta" class="text-xs text-muted mt-1">Loading…</div>
            </div>
        </div>
    </div>
</div>

<script src="https://s3.tradingview.com/tv.js"></script>
<script>
let tvWidget = null;
let currentTimeframe = '60';
const tvExchange = @json($tvExchange);
const tvPrefix = @json($tvPrefix);

function currentSymbol() {
    return document.getElementById('symbol').value;
}

function intervalMap(tf) {
    return tf === 'D' ? 'D' : tf;
}

function widgetSymbol(symbol) {
    return symbol.includes(':') ? symbol : `${tvExchange}:${tvPrefix}${symbol}`;
}

function ensureWidget() {
    const symbol = currentSymbol();
    const container = document.getElementById('tv-chart');
    container.innerHTML = '';

    try {
        tvWidget = new TradingView.widget({
            autosize: true,
            symbol: widgetSymbol(symbol),
            interval: intervalMap(currentTimeframe),
            timezone: 'Africa/Nairobi',
            theme: 'dark',
            style: '1',
            locale: 'en',
            enable_publishing: false,
            hide_top_toolbar: false,
            hide_legend: false,
            save_image: false,
            allow_symbol_change: false,
            container_id: 'tv-chart',
            studies: ['MASimple@tv-basicstudies', 'RSI@tv-basicstudies', 'ATR@tv-basicstudies'],
            disabled_features: ['use_localstorage_for_settings'],
            enabled_features: ['study_templates'],
            details: true,
            withdateranges: true,
            hotlist: false,
            calendar: false,
            support_host: 'https://www.tradingview.com',
        });
        document.getElementById('widget-status').textContent = 'TradingView widget loaded successfully.';
        document.getElementById('chart-meta').textContent = `${symbol} · ${currentTimeframe}`;
    } catch (e) {
        document.getElementById('widget-status').textContent = 'TradingView widget failed to load.';
        document.getElementById('chart-meta').textContent = `${symbol} · ${currentTimeframe} · widget unavailable`;
        console.error(e);
    }
}

async function loadSignal() {
    const symbol = currentSymbol();
    const r = await fetch(`/api/workspace/latest-signal?symbol=${encodeURIComponent(symbol)}`);
    const d = await r.json();
    const el = document.getElementById('signal-card');
    if (!d.signal) {
        el.innerHTML = '<div class="text-muted">No trained signal for this symbol yet.</div>';
        return;
    }

    const sig = d.signal;
    el.innerHTML = `
        <div class="flex items-center justify-between">
            <span class="font-medium ${sig.direction === 'buy' ? 'text-gain' : (sig.direction === 'sell' ? 'text-loss' : 'text-muted')}">${sig.direction.toUpperCase()}</span>
            <span class="font-mono text-xs text-muted">${sig.confidence}%</span>
        </div>
        <div class="mt-2 text-xs text-muted">Entry: ${sig.entry ?? '—'}<br>SL: ${sig.stop_loss ?? '—'}<br>TP: ${sig.take_profit ?? '—'}<br>Regime: ${sig.market_regime ?? '—'}</div>
        <div class="mt-2 text-xs text-muted">${sig.reasoning ?? ''}</div>
        <div class="mt-2 font-mono text-[10px] text-muted">Updated: ${sig.generated_at ? new Date(sig.generated_at).toLocaleString() : '—'}</div>
    `;
}

async function loadAnalysis() {
    const symbol = currentSymbol();
    const r = await fetch(`/api/workspace/analysis?symbol=${encodeURIComponent(symbol)}&timeframe=${encodeURIComponent(currentTimeframe)}&limit=300`);
    const d = await r.json();
    const el = document.getElementById('analysis-card');
    if (!d.ready) {
        el.innerHTML = `<div class="text-muted">${d.message ?? 'Analysis unavailable'}</div>`;
        return;
    }
    el.innerHTML = `
        <div class="grid grid-cols-2 gap-3">
            <div><div class="text-xs text-muted">Last Price</div><div class="font-mono text-lg">${Number(d.last_price).toFixed(5)}</div></div>
            <div><div class="text-xs text-muted">Bias</div><div class="font-medium">${d.signal_bias}</div></div>
            <div><div class="text-xs text-muted">RSI 14</div><div class="font-mono">${d.rsi14 ? Number(d.rsi14).toFixed(2) : '—'}</div></div>
            <div><div class="text-xs text-muted">ATR 14</div><div class="font-mono">${d.atr14 ? Number(d.atr14).toFixed(5) : '—'}</div></div>
            <div><div class="text-xs text-muted">MA 20</div><div class="font-mono">${d.ma20 ? Number(d.ma20).toFixed(5) : '—'}</div></div>
            <div><div class="text-xs text-muted">MA 50</div><div class="font-mono">${d.ma50 ? Number(d.ma50).toFixed(5) : '—'}</div></div>
        </div>
        <div class="mt-3 text-xs text-muted">Regime: ${d.regime} · Candles: ${d.candle_count}</div>
    `;
}

async function loadBroker() {
    const r = await fetch('/api/workspace/positions');
    const positions = await r.json();
    const el = document.getElementById('positions');
    el.innerHTML = positions.length
        ? positions.map(x => `<div class="border-b border-line py-2">
                <div class="flex items-center justify-between gap-2">
                    <span class="font-medium ${x.side === 'buy' ? 'text-gain' : 'text-loss'}">${x.side.toUpperCase()}</span>
                    <span class="font-mono text-xs text-muted">${x.instrument?.symbol ?? ''}</span>
                </div>
                <div class="text-xs text-muted mt-1">Mode: ${x.mode} · Lots: ${x.lot_size} · Opened: ${x.opened_at ?? '—'}</div>
            </div>`).join('')
        : '<div class="text-muted">No open positions</div>';
}

async function loadPerformance() {
    const r = await fetch('/api/workspace/performance');
    const d = await r.json();
    document.getElementById('performance').textContent = Number(d.net_profit).toLocaleString(undefined, { maximumFractionDigits: 2 });
    document.getElementById('performance-meta').textContent = `${d.trades} closed trades`;
}

async function loadHealth() {
    const symbol = currentSymbol();
    const r = await fetch(`/api/workspace/health?symbol=${encodeURIComponent(symbol)}&timeframe=${encodeURIComponent(currentTimeframe)}`);
    const d = await r.json();
    const chip = document.getElementById('health-chip');
    const candleAt = d.market_sync?.last_candle_at ? new Date(d.market_sync.last_candle_at).toLocaleString() : 'no candles';
    const signalAt = d.signal?.generated_at ? new Date(d.signal.generated_at).toLocaleString() : 'no signal';
    chip.textContent = `${d.provider} · candles ${candleAt} · signal ${signalAt}`;
}

function refreshWidget() {
    ensureWidget();
}

async function refreshAll() {
    try {
        refreshWidget();
        await loadSignal();
        await loadAnalysis();
        await loadHealth();
        await loadBroker();
        await loadPerformance();
    } catch (e) {
        console.error(e);
    }
}

document.getElementById('symbol').addEventListener('change', refreshAll);
document.querySelectorAll('.watch-item').forEach(btn => {
    btn.addEventListener('click', () => {
        document.getElementById('symbol').value = btn.dataset.symbol;
        refreshAll();
    });
});
document.querySelectorAll('.tf-btn').forEach(btn => {
    btn.addEventListener('click', () => {
        currentTimeframe = btn.dataset.tf;
        document.querySelectorAll('.tf-btn').forEach(x => x.classList.remove('border-brass/50', 'text-brass'));
        btn.classList.add('border-brass/50', 'text-brass');
        refreshAll();
    });
});

window.addEventListener('load', refreshAll);
setInterval(refreshAll, 15000);
</script>
@endsection
