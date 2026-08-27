<?php
namespace App\Http\Controllers;
use App\Models\AiSignal;
use App\Models\Instrument;
use App\Models\Trade;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

class TradingWorkspaceController extends Controller
{
    public function index(Request $r)
    {
        $instruments = Instrument::orderBy('symbol')->get();
        $symbol = $r->string('symbol')->toString() ?: optional($instruments->first())->symbol;

        return view('trading-workspace.index', compact('instruments', 'symbol'));
    }

    public function candles(Request $r)
    {
        $data = $r->validate([
            'symbol' => 'required|string|max:32',
            'limit' => 'nullable|integer|min:50|max:5000',
            'timeframe' => 'nullable|string|max:10',
        ]);

        $symbol = $data['symbol'];
        $limit = (int) ($data['limit'] ?? 500);
        $timeframe = strtolower($data['timeframe'] ?? 'h1');
        $provider = strtolower((string) (setting('ai_market_data_provider') ?: 'oanda'));

        if ($provider === 'twelve' || $provider === 'twelvedata' || $provider === 'twelve_data') {
            $apiKey = setting('twelve_data_api_key') ?: setting('market_data_api_key');
            if ($apiKey) {
                $interval = match ($timeframe) {
                    'm1' => '1min',
                    'm5' => '5min',
                    'm15' => '15min',
                    'm30' => '30min',
                    'h1' => '1h',
                    'h4' => '4h',
                    'd1', '1440' => '1day',
                    default => '1h',
                };

                try {
                    $response = Http::timeout(20)->get('https://api.twelvedata.com/time_series', [
                        'symbol' => $symbol,
                        'interval' => $interval,
                        'outputsize' => $limit,
                        'apikey' => $apiKey,
                    ]);
                    $response->throw();
                    $payload = $response->json();
                    $values = array_reverse((array) data_get($payload, 'values', []));
                    return response()->json([
                        'symbol' => $symbol,
                        'candles' => array_map(function ($x) {
                            return [
                                'time' => strtotime($x['datetime']),
                                'open' => (float) $x['open'],
                                'high' => (float) $x['high'],
                                'low' => (float) $x['low'],
                                'close' => (float) $x['close'],
                            ];
                        }, $values),
                    ]);
                } catch (\Throwable) {
                    // Fall back to local history if Twelve Data is unavailable.
                }
            }
        }

        $rows = DB::table('market_data_candles')
            ->where('symbol', $symbol)
            ->orderByDesc('time')
            ->limit($limit)
            ->get()
            ->reverse()
            ->values()
            ->map(fn ($c) => [
                'time' => strtotime($c->time),
                'open' => (float) $c->open,
                'high' => (float) $c->high,
                'low' => (float) $c->low,
                'close' => (float) $c->close,
            ]);

        return response()->json(['symbol' => $symbol, 'candles' => $rows]);
    }

    public function latestSignal(Request $r)
    {
        $symbol = $r->validate(['symbol' => 'required|string|max:32'])['symbol'];
        $signal = AiSignal::with('instrument')
            ->whereHas('instrument', fn ($q) => $q->where('symbol', $symbol))
            ->latest('generated_at')
            ->first();

        return response()->json([
            'symbol' => $symbol,
            'signal' => $signal ? [
                'direction' => $signal->direction,
                'confidence' => $signal->confidence,
                'entry' => $signal->entry,
                'stop_loss' => $signal->stop_loss,
                'take_profit' => $signal->take_profit,
                'market_regime' => $signal->market_regime,
                'reasoning' => $signal->reasoning,
                'generated_at' => optional($signal->generated_at)->toIso8601String(),
            ] : null,
        ]);
    }

    public function positions(Request $r)
    {
        return response()->json(Trade::with('instrument')->where('user_id', $r->user()->id)->where('status', 'open')->latest('opened_at')->get());
    }

    public function performance(Request $r)
    {
        $t = Trade::where('user_id', $r->user()->id)->where('status', 'closed')->orderBy('closed_at')->get();
        $equity = 0;
        $curve = [];
        foreach ($t as $x) {
            $equity += (float) ($x->profit_loss ?? 0);
            $curve[] = ['time' => optional($x->closed_at)->timestamp, 'value' => $equity];
        }
        return response()->json(['curve' => $curve, 'net_profit' => $equity, 'trades' => $t->count()]);
    }

    public function analysis(Request $r)
    {
        $data = $r->validate([
            'symbol' => 'required|string|max:32',
            'timeframe' => 'nullable|string|max:10',
            'limit' => 'nullable|integer|min:50|max:1000',
        ]);

        $symbol = $data['symbol'];
        $timeframe = strtoupper($data['timeframe'] ?? 'H1');
        $limit = (int) ($data['limit'] ?? 300);
        $candles = DB::table('market_data_candles')
            ->where('symbol', $symbol)
            ->where('timeframe', $timeframe)
            ->orderByDesc('time')
            ->limit($limit)
            ->get()
            ->reverse()
            ->values();

        if ($candles->count() < 60) {
            return response()->json(['symbol' => $symbol, 'timeframe' => $timeframe, 'ready' => false, 'message' => 'Not enough local data yet.']);
        }

        $closes = $candles->pluck('close')->map(fn ($v) => (float) $v)->values()->all();
        $highs = $candles->pluck('high')->map(fn ($v) => (float) $v)->values()->all();
        $lows = $candles->pluck('low')->map(fn ($v) => (float) $v)->values()->all();

        $ma = function (array $values, int $period): ?float {
            if (count($values) < $period) return null;
            return array_sum(array_slice($values, -$period)) / $period;
        };
        $rsi = function (array $values, int $period = 14): ?float {
            if (count($values) <= $period) return null;
            $gains = $losses = 0.0;
            for ($i = count($values) - $period; $i < count($values); $i++) {
                $delta = $values[$i] - $values[$i - 1];
                if ($delta >= 0) $gains += $delta; else $losses += abs($delta);
            }
            if ($losses == 0.0) return 100.0;
            $rs = ($gains / $period) / ($losses / $period);
            return 100 - (100 / (1 + $rs));
        };
        $atr = function (array $highs, array $lows, array $closes, int $period = 14): ?float {
            if (count($closes) <= $period) return null;
            $trs = [];
            for ($i = count($closes) - $period; $i < count($closes); $i++) {
                $prev = $closes[$i - 1];
                $trs[] = max(
                    $highs[$i] - $lows[$i],
                    abs($highs[$i] - $prev),
                    abs($lows[$i] - $prev)
                );
            }
            return array_sum($trs) / count($trs);
        };

        $last = (float) end($closes);
        $ma20 = $ma($closes, 20);
        $ma50 = $ma($closes, 50);
        $rsi14 = $rsi($closes, 14);
        $atr14 = $atr($highs, $lows, $closes, 14);
        $trend = $ma20 && $ma50 ? ($ma20 - $ma50) / max($last, 1e-8) : null;
        $regime = $trend !== null && abs($trend) > 0.00025 ? 'trending' : 'ranging';

        return response()->json([
            'symbol' => $symbol,
            'timeframe' => $timeframe,
            'ready' => true,
            'last_price' => $last,
            'ma20' => $ma20,
            'ma50' => $ma50,
            'rsi14' => $rsi14,
            'atr14' => $atr14,
            'trend' => $trend,
            'regime' => $regime,
            'signal_bias' => $ma20 !== null && $ma50 !== null ? ($ma20 > $ma50 ? 'bullish' : 'bearish') : 'neutral',
            'candle_count' => $candles->count(),
        ]);
    }
}
