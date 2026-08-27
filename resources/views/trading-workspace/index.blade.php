@extends('layouts.app')
@section('title', 'Trading Workspace — STETECH AI')
@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 py-6 sm:py-8 space-y-6">
    <div class="flex flex-col lg:flex-row lg:items-end lg:justify-between gap-4">
        <div>
            <p class="font-mono text-xs tracking-[0.2em] text-brass mb-2">LIVE TRADING VIEW</p>
            <h1 class="font-display text-3xl">Trading Workspace</h1>
            <p class="text-sm text-muted mt-2">Twelve Data-backed candles with trained AI overlays, broker state, and live trade monitoring.</p>
        </div>
        <div class="flex flex-wrap items-center gap-3">
            <label class="text-xs font-mono text-muted">SYMBOL</label>
            <select id="symbol" class="bg-panel border border-line rounded px-3 py-2 text-sm">
                @foreach($instruments as $i)
                    <option value="{{ $i->symbol }}" @selected($i->symbol===$symbol)>{{ $i->symbol }}</option>
                @endforeach
            </select>
            <div class="flex items-center gap-2">
                <button data-tf="M15" class="tf-btn border border-line rounded px-3 py-2 text-xs">M15</button>
                <button data-tf="H1" class="tf-btn border border-brass/50 text-brass rounded px-3 py-2 text-xs">H1</button>
                <button data-tf="H4" class="tf-btn border border-line rounded px-3 py-2 text-xs">H4</button>
                <button data-tf="D1" class="tf-btn border border-line rounded px-3 py-2 text-xs">D1</button>
            </div>
            <div id="health-chip" class="text-xs font-mono px-3 py-2 rounded border border-line text-muted">Checking feed…</div>
        </div>
    </div>

    <div class="grid lg:grid-cols-[1.7fr_0.9fr] gap-6">
        <div class="bg-panel border border-line rounded-lg overflow-hidden">
            <div class="flex items-center justify-between px-5 py-3 border-b border-line">
                <div>
                    <p class="font-mono text-[11px] tracking-[0.15em] text-muted">CANDLE CHART</p>
                    <p id="chart-meta" class="text-xs text-muted mt-1">Loading…</p>
                </div>
                <div class="flex items-center gap-2">
                    <span class="font-mono text-[11px] text-gain">Twelve Data</span>
                    <span class="font-mono text-[11px] text-muted">|</span>
                    <span class="font-mono text-[11px] text-gain">Trained signals</span>
                </div>
            </div>
            <div id="chart" style="height:560px;"></div>
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

<script src="https://unpkg.com/lightweight-charts/dist/lightweight-charts.standalone.production.js"></script>
<script>
let chart, candleSeries, signalEntrySeries, signalStopSeries, signalTakeSeries;
let currentTimeframe = 'H1';

function currentSymbol() {
    return document.getElementById('symbol').value;
}

function ensureChart() {
    if (chart) return;
    chart = LightweightCharts.createChart(document.getElementById('chart'), {
        height: 560,
        layout: {
            background: { color: 'transparent' },
            textColor: '#cbd5e1',
        },
        grid: {
            vertLines: { color: 'rgba(148,163,184,0.08)' },
            horzLines: { color: 'rgba(148,163,184,0.08)' },
        },
        crosshair: { mode: 1 },
        rightPriceScale: { borderColor: 'rgba(148,163,184,0.18)' },
        timeScale: { borderColor: 'rgba(148,163,184,0.18)' },
    });
    candleSeries = chart.addCandlestickSeries({
        upColor: '#22c55e',
        downColor: '#ef4444',
        borderUpColor: '#22c55e',
        borderDownColor: '#ef4444',
        wickUpColor: '#22c55e',
        wickDownColor: '#ef4444',
    });
    signalEntrySeries = chart.addLineSeries({ color: '#f59e0b', lineWidth: 2 });
    signalStopSeries = chart.addLineSeries({ color: '#ef4444', lineWidth: 2 });
    signalTakeSeries = chart.addLineSeries({ color: '#22c55e', lineWidth: 2 });
}

async function loadCandles() {
    const symbol = currentSymbol();
    const r = await fetch(`/api/workspace/candles?symbol=${encodeURIComponent(symbol)}&timeframe=${encodeURIComponent(currentTimeframe)}&limit=800`);
    const d = await r.json();
    ensureChart();
    candleSeries.setData(d.candles);
    chart.timeScale().fitContent();
    document.getElementById('chart-meta').textContent = `${symbol} · ${currentTimeframe} · ${d.candles.length} candles · refreshed ${new Date().toLocaleTimeString()}`;
}

async function loadSignal() {
    const symbol = currentSymbol();
    const r = await fetch(`/api/workspace/latest-signal?symbol=${encodeURIComponent(symbol)}`);
    const d = await r.json();
    const el = document.getElementById('signal-card');
    if (!d.signal) {
        el.innerHTML = '<div class="text-muted">No trained signal for this symbol yet.</div>';
        signalEntrySeries.setData([]);
        signalStopSeries.setData([]);
        signalTakeSeries.setData([]);
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

    const lastTime = candleSeries.getData().slice(-1)[0]?.time;
    if (lastTime && sig.direction !== 'wait') {
        signalEntrySeries.setData([{ time: lastTime, value: Number(sig.entry) }]);
        signalStopSeries.setData([{ time: lastTime, value: Number(sig.stop_loss) }]);
        signalTakeSeries.setData([{ time: lastTime, value: Number(sig.take_profit) }]);
        candleSeries.setMarkers([
            { time: lastTime, position: 'belowBar', color: '#f59e0b', shape: 'arrowUp', text: `Entry ${sig.direction}` },
            { time: lastTime, position: 'aboveBar', color: '#ef4444', shape: 'arrowDown', text: 'SL' },
            { time: lastTime, position: 'aboveBar', color: '#22c55e', shape: 'arrowDown', text: 'TP' },
        ]);
    } else {
        candleSeries.setMarkers([]);
    }
}

async function loadAnalysis() {
    const symbol = currentSymbol();
    const r = await fetch(`/api/workspace/analysis?symbol=${encodeURIComponent(symbol)}&timeframe=H1&limit=300`);
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

async function loadHealth() {
    const symbol = currentSymbol();
    const r = await fetch(`/api/workspace/health?symbol=${encodeURIComponent(symbol)}&timeframe=${encodeURIComponent(currentTimeframe)}`);
    const d = await r.json();
    const chip = document.getElementById('health-chip');
    const candleAt = d.market_sync?.last_candle_at ? new Date(d.market_sync.last_candle_at).toLocaleString() : 'no candles';
    const signalAt = d.signal?.generated_at ? new Date(d.signal.generated_at).toLocaleString() : 'no signal';
    chip.textContent = `${d.provider} · candles ${candleAt} · signal ${signalAt}`;
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

async function refreshAll() {
    try {
        await loadCandles();
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
document.querySelectorAll('.tf-btn').forEach(btn => {
    btn.addEventListener('click', () => {
        currentTimeframe = btn.dataset.tf;
        document.querySelectorAll('.tf-btn').forEach(x => x.classList.remove('border-brass/50', 'text-brass'));
        btn.classList.add('border-brass/50', 'text-brass');
        refreshAll();
    });
});
refreshAll();
setInterval(refreshAll, 15000);
</script>
@endsection
