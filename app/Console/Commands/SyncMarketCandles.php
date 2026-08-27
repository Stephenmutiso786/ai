<?php

namespace App\Console\Commands;

use App\Models\Instrument;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

class SyncMarketCandles extends Command
{
    protected $signature = 'ai:sync-market-candles {--symbol=} {--timeframe=H1} {--limit=500}';
    protected $description = 'Sync live market candles from the configured market-data provider into market_data_candles.';

    public function handle(): int
    {
        $provider = strtolower((string) (setting('ai_market_data_provider') ?: 'oanda'));
        $symbols = $this->option('symbol')
            ? [strtoupper((string) $this->option('symbol'))]
            : Instrument::where('is_active', true)->pluck('symbol')->map(fn ($s) => strtoupper($s))->all();
        $timeframe = strtoupper((string) $this->option('timeframe'));
        $limit = (int) $this->option('limit');

        if (! $symbols) {
            $this->warn('No active instruments found.');
            return self::SUCCESS;
        }

        $synced = 0;

        foreach ($symbols as $symbol) {
            $rows = $this->fetchCandles($provider, $symbol, $timeframe, $limit);
            foreach ($rows as $row) {
                DB::table('market_data_candles')->updateOrInsert(
                    [
                        'symbol' => $symbol,
                        'timeframe' => $timeframe,
                        'time' => $row['time'],
                    ],
                    [
                        'open' => $row['open'],
                        'high' => $row['high'],
                        'low' => $row['low'],
                        'close' => $row['close'],
                        'volume' => $row['volume'],
                        'updated_at' => now(),
                        'created_at' => now(),
                    ]
                );
                $synced++;
            }
        }

        $this->info("Synced {$synced} candles using {$provider}.");
        return self::SUCCESS;
    }

    private function fetchCandles(string $provider, string $symbol, string $timeframe, int $limit): array
    {
        return match ($provider) {
            'twelve', 'twelvedata', 'twelve_data' => $this->fetchTwelveData($symbol, $timeframe, $limit),
            default => $this->fetchOanda($symbol, $timeframe, $limit),
        };
    }

    private function fetchTwelveData(string $symbol, string $timeframe, int $limit): array
    {
        $apiKey = setting('twelve_data_api_key') ?: setting('market_data_api_key');
        if (! $apiKey) {
            throw new \RuntimeException('Twelve Data API key is not configured.');
        }

        $interval = match (strtolower($timeframe)) {
            'm1' => '1min',
            'm5' => '5min',
            'm15' => '15min',
            'm30' => '30min',
            'h1' => '1h',
            'h4' => '4h',
            'd1', '1440' => '1day',
            default => '1h',
        };

        $response = Http::timeout(30)->get('https://api.twelvedata.com/time_series', [
            'symbol' => $symbol,
            'interval' => $interval,
            'outputsize' => $limit,
            'apikey' => $apiKey,
        ]);
        $response->throw();
        $values = array_reverse((array) data_get($response->json(), 'values', []));

        return array_map(function (array $x): array {
            return [
                'time' => Carbon::parse($x['datetime'])->toDateTimeString(),
                'open' => (float) $x['open'],
                'high' => (float) $x['high'],
                'low' => (float) $x['low'],
                'close' => (float) $x['close'],
                'volume' => isset($x['volume']) ? (float) $x['volume'] : null,
            ];
        }, $values);
    }

    private function fetchOanda(string $symbol, string $timeframe, int $limit): array
    {
        $token = setting('oanda_api_token');
        $account = setting('oanda_account_id');
        $base = rtrim((string) (setting('oanda_api_url') ?: 'https://api-fxtrade.oanda.com'), '/');

        if (! $token || ! $account) {
            throw new \RuntimeException('OANDA credentials are not configured.');
        }

        $response = Http::timeout(30)
            ->withToken((string) $token)
            ->get("{$base}/v3/instruments/{$symbol}/candles", [
                'granularity' => $timeframe,
                'count' => $limit,
                'price' => 'M',
            ]);
        $response->throw();
        $candles = data_get($response->json(), 'candles', []);

        return array_values(array_filter(array_map(function (array $c): ?array {
            if (! ($c['complete'] ?? false)) {
                return null;
            }

            $mid = $c['mid'] ?? [];
            return [
                'time' => Carbon::parse($c['time'])->toDateTimeString(),
                'open' => (float) ($mid['o'] ?? 0),
                'high' => (float) ($mid['h'] ?? 0),
                'low' => (float) ($mid['l'] ?? 0),
                'close' => (float) ($mid['c'] ?? 0),
                'volume' => isset($c['volume']) ? (float) $c['volume'] : null,
            ];
        }, $candles)));
    }
}
