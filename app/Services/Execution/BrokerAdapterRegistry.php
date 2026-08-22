<?php
namespace App\Services\Execution;

use App\Models\BrokerAccount;
use RuntimeException;

class BrokerAdapterRegistry
{
    public function for(BrokerAccount $account): BrokerAdapterInterface
    {
        return match (strtoupper($account->platform)) {
            'OANDA' => app(OandaAdapter::class),
            'CTRADER' => app(CTraderAdapter::class),
            'MT5' => app(Mt5ConnectorAdapter::class),
            default => throw new RuntimeException("Unsupported broker platform [{$account->platform}]. Install a supported adapter before enabling live execution."),
        };
    }
}
