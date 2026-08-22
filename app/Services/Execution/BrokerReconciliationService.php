<?php
namespace App\Services\Execution;

use App\Models\BrokerAccount;
use App\Models\Trade;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/** Reconciles STETECH records with the broker source of truth. */
class BrokerReconciliationService
{
    public function reconcile(BrokerAccount $account): array
    {
        $adapter = app(BrokerAdapterRegistry::class)->for($account);
        $snapshot = $adapter->accountSnapshot($account);
        $positions = collect(data_get($snapshot, 'positions', []));

        DB::transaction(function () use ($account, $positions) {
            Trade::where('user_id', $account->user_id)->where('mode', 'live')->where('status', 'open')
                ->whereNotNull('broker_order_id')->get()->each(function (Trade $trade) use ($positions) {
                    $found = $positions->first(fn ($p) => (string) data_get($p, 'id', data_get($p, 'position_id')) === (string) $trade->broker_order_id);
                    // Absence from an open-position snapshot is not proof of closure.
                    // A definitive broker order/history lookup is required before mutating the trade state.
                    if (! $found) {
                        Log::warning('stetech.reconciliation_position_missing', [
                            'trade_id' => $trade->id,
                            'broker_account_id' => $account->id,
                        ]);
                    }
                });
        });

        $account->forceFill(['last_synced_at' => now(), 'connection_status' => 'connected'])->save();
        Log::info('stetech.broker_reconciled', ['broker_account_id'=>$account->id, 'positions'=>$positions->count()]);
        return $snapshot;
    }
}
