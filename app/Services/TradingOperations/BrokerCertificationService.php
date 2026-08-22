<?php
namespace App\Services\TradingOperations;
use App\Models\BrokerAccount;
use App\Models\BrokerCertification;
use App\Services\Execution\BrokerAdapterRegistry;

class BrokerCertificationService {
    public function __construct(private BrokerAdapterRegistry $registry) {}
    public function certify(BrokerAccount $account, int $userId): BrokerCertification {
        $cert=BrokerCertification::create(['broker_account_id'=>$account->id,'status'=>'running','checks'=>[],'details'=>[],'started_at'=>now()]);
        $checks=[]; $details=[];
        try {
            $adapter=$this->registry->for($account);
            $checks['connection']=$adapter->connect($account); if(!$checks['connection']) throw new \RuntimeException('Broker connection failed.');
            $snapshot=$adapter->accountSnapshot($account); $checks['account_snapshot']=isset($snapshot['balance']) || isset($snapshot['equity']); $details['snapshot']=$snapshot;
            foreach(['discoverSymbols','getInstrumentSpecifications','getOpenPositions'] as $method){ if(!method_exists($adapter,$method)) throw new \RuntimeException("Adapter does not implement {$method} required for certification."); }
            $symbols=$adapter->discoverSymbols($account); $checks['symbol_discovery']=is_array($symbols)&&count($symbols)>0; $details['symbol_count']=is_array($symbols)?count($symbols):0;
            if(!$checks['symbol_discovery']) throw new \RuntimeException('Broker returned no symbols.');
            $probe=$symbols[0]; $spec=$adapter->getInstrumentSpecifications($account,$probe); $checks['contract_specifications']=is_array($spec)&&isset($spec['min_lot'],$spec['max_lot'],$spec['lot_step']); $details['probe_symbol']=$probe; $details['contract_specifications']=$spec;
            $positions=$adapter->getOpenPositions($account); $checks['position_read']=is_array($positions); $details['open_positions_count']=is_array($positions)?count($positions):null;
            if(in_array(false,$checks,true)) throw new \RuntimeException('One or more certification checks failed.');
            $cert->update(['status'=>'passed','checks'=>$checks,'details'=>$details,'completed_at'=>now(),'certified_by'=>$userId]);
        } catch(\Throwable $e){ $details['error']=$e->getMessage(); $cert->update(['status'=>'failed','checks'=>$checks,'details'=>$details,'completed_at'=>now(),'certified_by'=>$userId]); }
        return $cert->fresh();
    }
}
