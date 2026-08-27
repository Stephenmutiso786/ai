<?php

namespace App\Console\Commands;

use App\Models\AiDataset;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

class BuildLocalMarketDataset extends Command
{
    protected $signature = 'ai:build-local-dataset {symbol} {timeframe=H1} {--output=}';
    protected $description = 'Export local market_data_candles into a trainable AI dataset CSV.';

    public function handle(): int
    {
        $symbol = strtoupper((string) $this->argument('symbol'));
        $timeframe = strtoupper((string) $this->argument('timeframe'));
        $output = $this->option('output') ?: storage_path("app/ai/datasets/{$symbol}-{$timeframe}-".now()->format('YmdHis').'.csv');

        $rows = DB::table('market_data_candles')
            ->where('symbol', $symbol)
            ->where('timeframe', $timeframe)
            ->orderBy('time')
            ->get(['time', 'open', 'high', 'low', 'close', 'volume']);

        if ($rows->isEmpty()) {
            $this->error("No local candles found for {$symbol} {$timeframe}.");
            return self::FAILURE;
        }

        File::ensureDirectoryExists(dirname($output));
        $handle = fopen($output, 'w');
        fwrite($handle, "timestamp,open,high,low,close,volume\n");
        foreach ($rows as $row) {
            fwrite($handle, implode(',', [
                optional($row->time)->format('Y-m-d H:i:s'),
                $row->open,
                $row->high,
                $row->low,
                $row->close,
                $row->volume ?? '',
            ])."\n");
        }
        fclose($handle);

        $dataset = AiDataset::create([
            'name' => "{$symbol} {$timeframe} local candles",
            'provider' => 'local',
            'instrument_symbol' => $symbol,
            'timeframe' => $timeframe,
            'row_count' => $rows->count(),
            'storage_uri' => $output,
            'status' => 'ready',
            'metadata' => [
                'source' => 'market_data_candles',
                'exported_at' => now()->toIso8601String(),
            ],
            'created_by' => auth()->id() ?: null,
        ]);

        $this->info("Dataset {$dataset->id} exported to {$output}");
        return self::SUCCESS;
    }
}
