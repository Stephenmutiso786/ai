<?php

namespace App\Services\Execution;

use App\Models\BrokerAccount;

/**
 * Contract for a real broker/platform integration (e.g. MT5, cTrader).
 * Implement this against your broker's supported API \u2014 never by
 * scripting the desktop terminal or storing a client's raw login
 * password. Nothing in app/ should call a broker directly except
 * through an adapter implementing this interface, invoked only by
 * ExecutionEngine.
 */
interface BrokerAdapterInterface
{
    public function connect(BrokerAccount $account): bool;

    public function accountSnapshot(BrokerAccount $account): array; // balance, equity, margin, open positions

    public function placeOrder(BrokerAccount $account, string $symbol, string $side, float $lotSize, ?float $stopLoss, ?float $takeProfit): array;

    public function closePosition(BrokerAccount $account, string $positionId): array;

    public function emergencyFlatten(BrokerAccount $account): array; // the kill switch

    /** Read-only operations required for certification and reconciliation. */
    public function discoverSymbols(BrokerAccount $account): array;
    public function getInstrumentSpecifications(BrokerAccount $account, string $symbol): array;
    public function getOpenPositions(BrokerAccount $account): array;
}
