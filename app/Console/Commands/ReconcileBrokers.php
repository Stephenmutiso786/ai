<?php
namespace App\Console\Commands;
use App\Jobs\ReconcileBrokerAccount;
use App\Models\BrokerAccount;
use Illuminate\Console\Command;
class ReconcileBrokers extends Command {
 protected $signature='stetech:brokers-reconcile'; protected $description='Queue reconciliation for all connected live broker accounts';
 public function handle(): int { BrokerAccount::where('connection_status','connected')->chunkById(100, fn($rows)=>$rows->each(fn($a)=>ReconcileBrokerAccount::dispatch($a->id))); $this->info('Broker reconciliation queued.'); return self::SUCCESS; }
}
