<?php
namespace App\Services\AI;

use App\Models\AiBacktest;
use App\Models\AiDataset;
use App\Models\AiTrainingRun;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Symfony\Component\Process\Process;

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
        $dataset = $run->dataset;

        if ($url) {
            try {
                $response = Http::timeout(20)->acceptJson()->withToken((string)$token)->post(rtrim($url,'/').'/training-runs', [
                    'run_id'=>$run->id,'model_id'=>$run->ai_model_id,'dataset_id'=>$run->ai_dataset_id,'config'=>$run->config,
                ]);
                $response->throw();
                $run->update([
                    'job_reference'=>$response->json('job_reference'),
                    'status'=>$response->json('status') === 'completed' ? 'completed' : 'running',
                    'started_at'=>now(),
                    'finished_at'=>$response->json('status') === 'completed' ? now() : null,
                    'metrics'=>$response->json('metrics'),
                ]);
                if ($response->json('status') === 'completed') {
                    $run->model->update(['status'=>'trained','metrics'=>$response->json('metrics', [])]);
                }
                return;
            } catch (\Throwable $e) {
                Log::warning('Remote AI training failed, falling back to local trainer.', ['run_id' => $run->id, 'error' => $e->getMessage()]);
            }
        }

        if (! $dataset || ! $dataset->storage_uri || ! file_exists($dataset->storage_uri)) {
            $run->update([
                'status' => 'failed',
                'error_message' => 'Dataset file not found for local training fallback.',
                'started_at' => now(),
                'finished_at' => now(),
            ]);
            return;
        }

        $output = base_path('ai-service/artifacts/model-'.$run->ai_model_id.'.joblib');
        $script = base_path('ai-service/scripts/train_from_csvs.py');
        $cmd = [
            'python3',
            $script,
            '--output',
            $output,
            '--max-rows-per-file',
            (string) (int) ($run->config['max_rows_per_file'] ?? 20000),
            $dataset->storage_uri,
        ];

        $process = new Process($cmd, base_path());
        $process->setTimeout(3600);
        $process->run();

        $run->update([
            'job_reference' => 'local-train-'.$run->id,
            'status' => $process->isSuccessful() ? 'completed' : 'failed',
            'started_at' => now(),
            'finished_at' => now(),
            'metrics' => $process->isSuccessful() ? ['mode' => 'local', 'output' => $output] : ['mode' => 'local', 'error' => $process->getErrorOutput() ?: $process->getOutput()],
            'error_message' => $process->isSuccessful() ? null : trim($process->getErrorOutput() ?: $process->getOutput()),
        ]);

        if ($process->isSuccessful()) {
            $metrics = ['mode' => 'local', 'artifact' => basename($output)];
            $run->model->forceFill([
                'status' => 'trained',
                'artifact_uri' => $output,
                'metrics' => $metrics,
            ])->save();
        }
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
