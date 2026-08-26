<?php
namespace App\Services\Execution;

use App\Models\BrokerAccount;
use Illuminate\Support\Facades\Http;
use RuntimeException;

/**
 * Real MT5 connector adapter. STETECH does not scrape terminals or collect passwords.
 * A client-authorized connector installed beside an MT5 terminal exposes a mutually
 * authenticated HTTPS endpoint to the STETECH gateway.
 */
class Mt5ConnectorAdapter implements BrokerAdapterInterface
{
    private function payload(BrokerAccount $a): array
    {
        $payload = $a->credentialPayload();

        if (! empty($payload['connector_url']) && ! empty($payload['connector_token'])) {
            return $payload;
        }

        $url = setting('mt5_bridge_url');
        $token = setting('mt5_bridge_token');

        if ($url && $token) {
            $payload['connector_url'] = $url;
            $payload['connector_token'] = $token;
        }

        return $payload;
    }
    private function http(BrokerAccount $a)
    {
        $p=$this->payload($a);
        if(empty($p['connector_url']) || empty($p['connector_token'])) throw new RuntimeException('MT5 connector is not paired.');
        return Http::baseUrl(rtrim($p['connector_url'],'/'))->withToken($p['connector_token'])->acceptJson()->timeout(25)->retry(2,500);
    }
    public function connect(BrokerAccount $a): bool { try { return $this->http($a)->get('/health')->successful(); } catch(\Throwable){ return false; } }
    public function accountSnapshot(BrokerAccount $a): array { return $this->http($a)->get('/account')->throw()->json(); }
    public function placeOrder(BrokerAccount $a,string $symbol,string $side,float $lot,?float $sl,?float $tp): array
    { return $this->http($a)->post('/orders',['symbol'=>$symbol,'side'=>$side,'volume'=>$lot,'stop_loss'=>$sl,'take_profit'=>$tp])->throw()->json(); }
    public function closePosition(BrokerAccount $a,string $positionId): array { return $this->http($a)->post("/positions/{$positionId}/close")->throw()->json(); }
    public function emergencyFlatten(BrokerAccount $a): array { return $this->http($a)->post('/positions/close-all')->throw()->json(); }
    public function discoverSymbols(BrokerAccount $a): array { return $this->http($a)->get('/symbols')->throw()->json('symbols',[]); }
    public function getInstrumentSpecifications(BrokerAccount $a,string $symbol): array { return $this->http($a)->get('/symbols/'.rawurlencode($symbol).'/specification')->throw()->json(); }
    public function getOpenPositions(BrokerAccount $a): array { return $this->http($a)->get('/positions')->throw()->json('positions',[]); }

}
