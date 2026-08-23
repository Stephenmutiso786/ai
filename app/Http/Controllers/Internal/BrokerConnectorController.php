<?php
namespace App\Http\Controllers\Internal;
use App\Http\Controllers\Controller;
use App\Models\BrokerAccount;
use App\Services\Execution\BrokerAdapterRegistry;
use Illuminate\Http\Request;
class BrokerConnectorController extends Controller
{
 public function test(Request $r, BrokerAccount $account, BrokerAdapterRegistry $registry) {
  abort_unless($r->user()->can('access-admin') || $r->user()->id === $account->user_id,403);
  $adapter=$registry->for($account); $ok=$adapter->connect($account);
  $account->update(['connection_status'=>$ok?'connected':'failed','connected_at'=>$ok?now():$account->connected_at,'verified_at'=>$ok?now():$account->verified_at]);
  return response()->json(['connected'=>$ok]);
 }
 public function snapshot(Request $r, BrokerAccount $account, BrokerAdapterRegistry $registry) {
  abort_unless($r->user()->can('access-admin') || $r->user()->id === $account->user_id,403);
  $snapshot=$registry->for($account)->accountSnapshot($account); $account->update(['last_synced_at'=>now(),'connection_status'=>'connected','verified_at'=>now()]); return response()->json($snapshot);
 }
}
