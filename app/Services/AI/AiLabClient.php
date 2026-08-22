<?php
namespace App\Services\AI;

use App\Models\AiBacktest;
use App\Models\AiTrainingRun;
use Illuminate\Support\Facades\Http;

/**
 * Production boundary between Laravel and the isolated Python AI service.
 * Laravel never trains models in a web request. It creates auditable jobs and
 * delegates heavy work to the AI service/queue.
 */
class AiLabClient
{
    public function startTraining(AiTrainingRun $run): void
    {
        $url = setting('ai_service_url');
        $token = setting('ai_service_token');
        if (! $url) return; // job remains queued until AI infrastructure is configured.
        $response = Http::timeout(10)->acceptJson()->withToken((string)$token)->post(rtrim($url,'/').'/training-runs', [
            'run_id'=>$run->id,'model_id'=>$run->ai_model_id,'dataset_id'=>$run->ai_dataset_id,'config'=>$run->config,
        ]);
        $response->throw();
        $run->update(['job_reference'=>$response->json('job_reference'), 'status'=>$response->json('status') === 'completed' ? 'completed' : 'running', 'started_at'=>now(), 'finished_at'=>$response->json('status') === 'completed' ? now() : null, 'metrics'=>$response->json('metrics')]);
        if ($response->json('status') === 'completed') $run->model->update(['status'=>'trained','metrics'=>$response->json('metrics', [])]);
    }

    public function startBacktest(AiBacktest $backtest): void
    {
        $url = setting('ai_service_url'); $token = setting('ai_service_token');
        if (! $url) return;
        $response = Http::timeout(10)->acceptJson()->withToken((string)$token)->post(rtrim($url,'/').'/backtests', [
            'backtest_id'=>$backtest->id,'model_id'=>$backtest->ai_model_id,'instrument'=>$backtest->instrument_symbol,
            'timeframe'=>$backtest->timeframe,'starts_at'=>$backtest->starts_at?->toIso8601String(),'ends_at'=>$backtest->ends_at?->toIso8601String(),'config'=>$backtest->config,
        ]);
        $response->throw();
        $backtest->update(['status'=>$response->json('status') === 'completed' ? 'completed' : 'running', 'results'=>$response->json('results')]);
    }
}
