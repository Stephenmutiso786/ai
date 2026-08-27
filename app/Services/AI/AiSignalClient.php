<?php
namespace App\Services\AI;
use App\Models\AiModel;
use Illuminate\Support\Facades\Http;

class AiSignalClient
{
    public function liveSignal(AiModel $model, string $symbol, string $timeframe): array
    {
        $url = setting('ai_service_url');
        $token = setting('ai_service_token');
        if (! $url || ! $token) {
            throw new \RuntimeException('AI service URL/token are not configured.');
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
            default => throw new \RuntimeException('Unsupported AI market-data provider: '.$provider),
        };
        foreach ($providerConfig as $key => $value) {
            if ($value === null || $value === '') {
                throw new \RuntimeException('Required market-data credential is missing from Super Admin Settings: '.$key);
            }
        }
        $plan = auth()->user()?->subscription?->plan;
        $providerConfig['plan'] = [
            'trading_mode' => $plan?->recommendedTradingMode() ?? 'signals_only',
            'min_confidence' => $plan?->automation_allowed ? 66 : 60,
            'entry_threshold' => $plan?->automation_allowed ? 0.57 : 0.55,
            'exit_threshold' => $plan?->automation_allowed ? 0.43 : 0.45,
            'plan_name' => $plan?->name,
            'plan_slug' => $plan?->slug,
        ];
        $response = Http::timeout(20)->acceptJson()->withToken((string) $token)->post(rtrim($url, '/').'/signals/live', [
            'model_id' => $model->id,
            'symbol' => $symbol,
            'timeframe' => $timeframe,
            'provider' => $provider,
            'provider_config' => $providerConfig,
            'count' => 300,
        ]);

        $response->throw();

        $payload = $response->json();
        if (! is_array($payload)) {
            throw new \RuntimeException('AI service returned an invalid JSON payload.');
        }

        foreach (['direction', 'confidence', 'entry', 'stop_loss', 'take_profit', 'risk_reward', 'market_regime'] as $required) {
            if (! array_key_exists($required, $payload)) {
                throw new \RuntimeException("AI service response missing required field [{$required}].");
            }
        }

        return $payload;
    }
}
