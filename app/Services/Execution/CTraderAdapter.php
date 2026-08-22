<?php
namespace App\Services\Execution;

use App\Models\BrokerAccount;
use Illuminate\Support\Facades\Http;
use RuntimeException;

/** cTrader Open API adapter. Requires a real OAuth access token and cTID account id. */
class CTraderAdapter implements BrokerAdapterInterface
{
    private function cfg(BrokerAccount $a): array { return $a->credentialPayload(); }
    private function http(BrokerAccount $a)
    {
        $p = $this->cfg($a);
        if (empty($p['access_token']) || empty($p['ctid_trader_account_id'])) throw new RuntimeException('cTrader access token/account id is missing.');
        return Http::baseUrl(rtrim($p['gateway_url'] ?? config('services.ctrader.gateway_url'), '/'))
            ->withToken($p['access_token'])->acceptJson()->timeout(20)->retry(2, 300);
    }
    public function connect(BrokerAccount $a): bool
    {
        try { return $this->accountSnapshot($a) !== []; } catch (\Throwable) { return false; }
    }
    public function accountSnapshot(BrokerAccount $a): array
    {
        $p=$this->cfg($a); $id=$p['ctid_trader_account_id'];
        $r=$this->http($a)->get("/accounts/{$id}")->throw()->json();
        return ['balance'=>(float)($r['balance'] ?? 0),'equity'=>(float)($r['equity'] ?? 0),'margin_available'=>(float)($r['freeMargin'] ?? 0),'currency'=>$r['currency'] ?? null,'raw'=>$r];
    }
    public function placeOrder(BrokerAccount $a,string $symbol,string $side,float $lot,?float $sl,?float $tp): array
    {
        $p=$this->cfg($a); $id=$p['ctid_trader_account_id'];
        $payload=['accountId'=>$id,'symbol'=>$symbol,'side'=>strtoupper($side),'volume'=>$lot];
        if($sl!==null)$payload['stopLoss']=$sl; if($tp!==null)$payload['takeProfit']=$tp;
        return $this->http($a)->post('/orders/market',$payload)->throw()->json();
    }
    public function closePosition(BrokerAccount $a,string $positionId): array { return $this->http($a)->post("/positions/{$positionId}/close")->throw()->json(); }
    public function emergencyFlatten(BrokerAccount $a): array { $p=$this->cfg($a); return $this->http($a)->post('/positions/close-all',['accountId'=>$p['ctid_trader_account_id']])->throw()->json(); }
    public function discoverSymbols(BrokerAccount $a): array { $p=$this->cfg($a); return $this->http($a)->get('/symbols',['accountId'=>$p['ctid_trader_account_id']])->throw()->json('symbols',[]); }
    public function getInstrumentSpecifications(BrokerAccount $a,string $symbol): array { $p=$this->cfg($a); return $this->http($a)->get('/symbols/'.rawurlencode($symbol).'/specification',['accountId'=>$p['ctid_trader_account_id']])->throw()->json(); }
    public function getOpenPositions(BrokerAccount $a): array { $p=$this->cfg($a); return $this->http($a)->get('/positions',['accountId'=>$p['ctid_trader_account_id']])->throw()->json('positions',[]); }

}
