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
        <div class="flex items-center gap-3">
            <label class="text-xs font-mono text-muted">SYMBOL</label>
            <select id="symbol" class="bg-panel border border-line rounded px-3 py-2 text-sm">
                @foreach($instruments as $i)
                    <option value="{{ $i->symbol }}" @selected($i->symbol===$symbol)>{{ $i->symbol }}</option>
                @endforeach
            </select>
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
    const r = await fetch(`/api/workspace/candles?symbol=${encodeURIComponent(symbol)}&timeframe=h1&limit=800`);
    const d = await r.json();
    ensureChart();
    candleSeries.setData(d.candles);
    chart.timeScale().fitContent();
    document.getElementById('chart-meta').textContent = `${symbol} · ${d.candles.length} candles · refreshed ${new Date().toLocaleTimeString()}`;
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
    }
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
        await loadBroker();
        await loadPerformance();
    } catch (e) {
        console.error(e);
    }
}

document.getElementById('symbol').addEventListener('change', refreshAll);
refreshAll();
setInterval(refreshAll, 15000);
</script>
@endsection
