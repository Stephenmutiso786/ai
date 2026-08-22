<?php
namespace App\Jobs;
use App\Models\BrokerAccount;
use App\Services\Execution\BrokerReconciliationService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
class ReconcileBrokerAccount implements ShouldQueue {
 use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
 public int $tries=3; public int $timeout=60;
 public function __construct(public int $brokerAccountId) {}
 public function handle(BrokerReconciliationService $service): void { $a=BrokerAccount::find($this->brokerAccountId); if($a && $a->connection_status==='connected') $service->reconcile($a); }
}
