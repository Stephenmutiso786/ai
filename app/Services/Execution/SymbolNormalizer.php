<?php
namespace App\Services\Execution;
use App\Models\BrokerAccount;
class SymbolNormalizer {
 public function toBroker(BrokerAccount $account,string $canonical): string {
  $map=(array) data_get($account->credentialPayload(),'symbol_map',[]);
  return $map[$canonical] ?? $canonical;
 }
}
