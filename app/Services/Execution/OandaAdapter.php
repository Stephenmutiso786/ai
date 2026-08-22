<?php
namespace App\Services\Execution;
use App\Models\BrokerAccount;
use Illuminate\Support\Facades\Http;
use RuntimeException;
class OandaAdapter implements BrokerAdapterInterface {
    private function cfg(): array { $p=app(BrokerAccount::class); return []; }
    private function payload(BrokerAccount $a): array { return $a->credentialPayload(); }
    private function request(BrokerAccount $a) {
        $p=$this->payload($a); $base=$p['api_url'] ?? 'https://api-fxpractice.oanda.com';
        return Http::baseUrl($base)->withToken($p['access_token'])->acceptJson()->timeout(20);
    }
    public function connect(BrokerAccount $a): bool { try { $p=$this->payload($a); $r=$this->request($a)->get('/v3/accounts/'.($p['account_id'] ?? $a->account_number).'/summary'); return $r->successful(); } catch (\Throwable) { return false; } }
    public function accountSnapshot(BrokerAccount $a): array { $p=$this->payload($a); $r=$this->request($a)->get('/v3/accounts/'.($p['account_id'] ?? $a->account_number).'/summary')->throw()->json('account'); return ['balance'=>(float)$r['balance'],'equity'=>(float)$r['NAV'],'margin_available'=>(float)$r['marginAvailable'],'currency'=>$r['currency']]; }
    public function placeOrder(BrokerAccount $a,string $symbol,string $side,float $lot,?float $sl,?float $tp): array { $p=$this->payload($a); $units=(int)round($lot*100000)*($side==='sell'?-1:1); $order=['type'=>'MARKET','instrument'=>$symbol,'units'=>(string)$units,'timeInForce'=>'FOK','positionFill'=>'DEFAULT']; if($sl)$order['stopLossOnFill']=['price'=>(string)$sl]; if($tp)$order['takeProfitOnFill']=['price'=>(string)$tp]; return $this->request($a)->post('/v3/accounts/'.($p['account_id'] ?? $a->account_number).'/orders',['order'=>$order])->throw()->json(); }
    public function closePosition(BrokerAccount $a,string $id): array { $p=$this->payload($a); return $this->request($a)->put('/v3/accounts/'.($p['account_id'] ?? $a->account_number).'/trades/'.$id.'/close')->throw()->json(); }
    public function emergencyFlatten(BrokerAccount $a): array { $p=$this->payload($a); return $this->request($a)->put('/v3/accounts/'.($p['account_id'] ?? $a->account_number).'/positions/close',['longUnits'=>'ALL','shortUnits'=>'ALL'])->throw()->json(); }
    public function discoverSymbols(BrokerAccount $a): array { $p=$this->payload($a); $id=$p['account_id'] ?? $a->account_number; $r=$this->request($a)->get("/v3/accounts/{$id}/instruments")->throw()->json('instruments',[]); return array_values(array_filter(array_map(fn($x)=>$x['name']??null,$r))); }
    public function getInstrumentSpecifications(BrokerAccount $a,string $symbol): array { $p=$this->payload($a); $id=$p['account_id'] ?? $a->account_number; $items=$this->request($a)->get("/v3/accounts/{$id}/instruments",['instruments'=>$symbol])->throw()->json('instruments',[]); $x=$items[0]??[]; return ['min_lot'=>(float)($x['minimumTradeSize']??0.01),'max_lot'=>(float)($x['maximumOrderUnits']??100),'lot_step'=>(float)($x['minimumTradeSize']??0.01),'raw'=>$x]; }
    public function getOpenPositions(BrokerAccount $a): array { $p=$this->payload($a); $id=$p['account_id'] ?? $a->account_number; return $this->request($a)->get("/v3/accounts/{$id}/openPositions")->throw()->json('positions',[]); }

}
