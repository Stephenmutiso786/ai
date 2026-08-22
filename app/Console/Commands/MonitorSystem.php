<?php
namespace App\Console\Commands;
use App\Services\Operations\SystemMonitor;
use Illuminate\Console\Command;
class MonitorSystem extends Command {
    protected $signature='stetech:monitor'; protected $description='Run production health checks and create actionable incidents';
    public function handle(SystemMonitor $monitor): int { $result=$monitor->run(); $this->info(json_encode($result)); return $result['status']==='critical' ? self::FAILURE : self::SUCCESS; }
}
