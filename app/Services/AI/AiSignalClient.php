<?php
namespace App\Services\AI;
use App\Models\AiModel;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

class AiSignalClient
{
    public function liveSignal(AiModel $model, string $symbol, string $timeframe): array
    {
        $url = setting('ai_service_url'); $token = setting('ai_service_token');
        if (! $url || ! $token) {
            return $this->fallbackSignal($symbol, $timeframe, 'AI service URL/token are not configured.');
        }
        $provider = strtolower((string) (setting('ai_market_data_provider') ?: 'oanda'));
        $providerConfig = match ($provider) {
            'oanda' => [
                'api_token' => setting('oanda_api_token'),
                'account_id' => setting('oanda_account_id'),
                'base_url' => setting('oanda_api_url') ?: 'https://api-fxtrade.oanda.com',
            ],
            'twelve', 'twelvedata', 'twelve_data' => [
                'api_key' => setting('twelve_data_api_key') ?: setting('market_data_api_key'),
            ],
            default => null,
        };
        if ($providerConfig === null) {
            return $this->fallbackSignal($symbol, $timeframe, 'Unsupported AI market-data provider: '.$provider);
        }
        foreach ($providerConfig as $key => $value) {
            if ($value === null || $value === '') {
                return $this->fallbackSignal($symbol, $timeframe, 'Required market-data credential is missing from Super Admin Settings: '.$key);
            }
        }
        try {
            $response = Http::timeout(20)->acceptJson()->withToken((string)$token)->post(rtrim($url,'/').'/signals/live', [
                'model_id'=>$model->id,'symbol'=>$symbol,'timeframe'=>$timeframe,'provider'=>$provider,'provider_config'=>$providerConfig,'count'=>300,
            ]);
            $response->throw();
            return $response->json();
        } catch (\Throwable $e) {
            return $this->fallbackSignal($symbol, $timeframe, $e->getMessage());
        }
    }

    protected function fallbackSignal(string $symbol, string $timeframe, string $reason): array
    {
        $rows = DB::table('market_data_candles')
            ->where('symbol', $symbol)
            ->where('timeframe', $timeframe)
            ->orderByDesc('time')
            ->limit(20)
            ->get()
            ->sortBy('time')
            ->values();

        if ($rows->count() < 2) {
            return [
                'direction' => 'wait',
                'confidence' => 0,
                'entry' => null,
                'stop_loss' => null,
                'take_profit' => null,
                'risk_reward' => null,
                'market_regime' => 'insufficient_data',
                'reasoning' => 'Fallback signal: insufficient market data. '.$reason,
            ];
        }

        $first = (float) $rows->first()->close;
        $last = (float) $rows->last()->close;
        $changePct = $first > 0 ? (($last - $first) / $first) * 100 : 0;
        $abs = abs($changePct);

        if ($abs < 0.02) {
            $direction = 'wait';
        } elseif ($changePct > 0) {
            $direction = 'buy';
        } else {
            $direction = 'sell';
        }

        $confidence = (int) min(95, max(25, round($abs * 120)));

        return [
            'direction' => $direction,
            'confidence' => $confidence,
            'entry' => $last,
            'stop_loss' => $direction === 'buy' ? $last * 0.998 : ($direction === 'sell' ? $last * 1.002 : $last),
            'take_profit' => $direction === 'buy' ? $last * 1.004 : ($direction === 'sell' ? $last * 0.996 : $last),
            'risk_reward' => $direction === 'wait' ? null : 2.0,
            'market_regime' => $abs < 0.02 ? 'range' : 'trend',
            'reasoning' => 'Fallback signal generated from recent candle momentum. '.$reason,
        ];
    }
}
