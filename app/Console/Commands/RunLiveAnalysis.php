<?php

namespace App\Console\Commands;

use App\Models\Instrument;
use App\Services\Signals\SignalEngine;
use Illuminate\Console\Command;

class RunLiveAnalysis extends Command
{
    protected $signature = 'ai:run-live-analysis {--timeframe=H1} {--symbol=}';
    protected $description = 'Generate fresh AI signals for active instruments using real market data.';

    public function handle(SignalEngine $engine): int
    {
        $timeframe = strtoupper((string) $this->option('timeframe'));
        $query = Instrument::where('is_active', true);

        if ($this->option('symbol')) {
            $query->where('symbol', strtoupper((string) $this->option('symbol')));
        }

        $instruments = $query->get();

        if ($instruments->isEmpty()) {
            $this->warn('No active instruments found.');
            return self::SUCCESS;
        }

        $count = 0;
        foreach ($instruments as $instrument) {
            try {
                $engine->generateFor($instrument, $timeframe, true);
                $count++;
            } catch (\Throwable $e) {
                $this->error($instrument->symbol . ': ' . $e->getMessage());
            }
        }

        $this->info("Generated {$count} live signals on {$timeframe}.");
        return self::SUCCESS;
    }
}
