@extends('layouts.app')
@section('content')
<div class="max-w-7xl mx-auto p-6 space-y-6">
 <div class="flex flex-wrap justify-between gap-3"><div><h1 class="text-2xl font-bold">Trading Workspace</h1><p class="text-sm opacity-70">Live visualization of STETECH market data, AI decisions and account performance.</p></div><select id="symbol" class="border rounded p-2">@foreach($instruments as $i)<option value="{{ $i->symbol }}" @selected($i->symbol===$symbol)>{{ $i->symbol }}</option>@endforeach</select></div>
 <div class="grid lg:grid-cols-3 gap-6"><div class="lg:col-span-2 bg-white dark:bg-slate-900 rounded-xl p-4 shadow"><div id="chart" style="height:520px"></div></div><div class="space-y-4"><div class="bg-white dark:bg-slate-900 rounded-xl p-4 shadow"><h2 class="font-semibold">AI / Trade Overlay</h2><div id="positions" class="text-sm mt-3">Loading positions…</div></div><div class="bg-white dark:bg-slate-900 rounded-xl p-4 shadow"><h2 class="font-semibold">Performance</h2><div id="performance" class="text-2xl font-bold mt-3">—</div></div></div></div>
</div>
<script src="https://unpkg.com/lightweight-charts/dist/lightweight-charts.standalone.production.js"></script>
<script>
let chart,series; async function load(){const symbol=document.getElementById('symbol').value; const r=await fetch(`/api/workspace/candles?symbol=${encodeURIComponent(symbol)}`);const d=await r.json(); if(!chart){chart=LightweightCharts.createChart(document.getElementById('chart'),{height:520,layout:{background:{color:'transparent'}}});series=chart.addCandlestickSeries();}series.setData(d.candles);chart.timeScale().fitContent();}
async function side(){let p=await (await fetch('/api/workspace/positions')).json();document.getElementById('positions').innerHTML=p.length?p.map(x=>`<div class="border-b py-2"><b>${x.side.toUpperCase()}</b> ${x.instrument?.symbol??''}<br><small>${x.mode} • ${x.lot_size} lots</small></div>`).join(''):'No open positions';let q=await (await fetch('/api/workspace/performance')).json();document.getElementById('performance').textContent=Number(q.net_profit).toLocaleString(undefined,{maximumFractionDigits:2});}
document.getElementById('symbol').addEventListener('change',load);load();side();setInterval(side,15000);setInterval(load,60000);
</script>
@endsection
