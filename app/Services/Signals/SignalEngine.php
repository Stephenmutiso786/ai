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
        $model = AiModel::query()
            ->whereIn('status', ['live', 'approved', 'paper', 'shadow', 'trained', 'validating'])
            ->orderByRaw("CASE status WHEN 'live' THEN 0 WHEN 'approved' THEN 1 WHEN 'paper' THEN 2 WHEN 'shadow' THEN 3 WHEN 'trained' THEN 4 WHEN 'validating' THEN 5 ELSE 6 END")
            ->latest('updated_at')
            ->first();

        if (! $model) {
            throw new \RuntimeException('No AI model is available. Create or deploy a model in AI Lab first.');
        }
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
