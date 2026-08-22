<?php
namespace App\Services\Signals;
use App\Models\AiModel;
use App\Models\AiSignal;
use App\Models\Instrument;
use App\Services\AI\AiSignalClient;

class SignalEngine
{
    public function __construct(private AiSignalClient $client) {}
    public function generateFor(Instrument $instrument, string $timeframe = 'H1'): AiSignal
    {
        $model = AiModel::query()->where('status','live')->latest('updated_at')->first();
        if (! $model) throw new \RuntimeException('No approved live AI model is deployed.');
        $signal = $this->client->liveSignal($model, $instrument->symbol, $timeframe);
        return AiSignal::create([
            'instrument_id'=>$instrument->id,'direction'=>$signal['direction'],'confidence'=>$signal['confidence'],
            'entry'=>$signal['entry'],'stop_loss'=>$signal['stop_loss'],'take_profit'=>$signal['take_profit'],
            'risk_reward'=>$signal['risk_reward'],'market_regime'=>$signal['market_regime'],
            'reasoning'=>($signal['reasoning'] ?? 'Model inference').' Model '.$model->name.' '.$model->version,
            'generated_at'=>now(),
        ]);
    }
}
