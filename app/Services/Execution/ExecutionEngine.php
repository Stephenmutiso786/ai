<?php

namespace App\Services\Execution;

use App\Models\AiSignal;
use App\Models\Trade;
use App\Models\User;
use App\Services\RiskDecision;
use Illuminate\Support\Facades\Log;

/**
 * Every order STETECH AI places goes through here, and here only.
 *
 * Live execution requires ALL of the following simultaneously:
 *   1. LIVE_TRADING_ENABLED=true (checked inside placeLive() itself, not
 *      just in middleware -- see comment there).
 *   2. services.broker_execution_mode=live.
 *   3. A connected BrokerAccount with trading_mode=fully_automatic.
 *   4. A registered, working BrokerAdapter for that account's platform.
 * Any one of these being false/missing means paper mode or a thrown
 * exception -- never a silent no-op that looks like success.
 */
class ExecutionEngine
{
    public function place(User $user, AiSignal $signal, RiskDecision $decision, ?\App\Models\BrokerAccount $brokerAccount = null): Trade
    {
        if (! $decision->approved) {
            throw new \RuntimeException('ExecutionEngine received a rejected RiskDecision. This should never happen.');
        }

        $mode = config('services.broker_execution_mode', 'paper');

        return match ($mode) {
            'paper' => $this->placePaper($user, $signal, $decision),
            'live' => $this->placeLive($user, $signal, $decision, $brokerAccount),
            default => throw new \RuntimeException("Unknown execution mode [{$mode}]."),
        };
    }

    protected function placePaper(User $user, AiSignal $signal, RiskDecision $decision): Trade
    {
        $trade = Trade::create([
            'user_id' => $user->id,
            'ai_signal_id' => $signal->id,
            'instrument_id' => $signal->instrument_id,
            'side' => $signal->direction,
            'mode' => 'paper',
            'lot_size' => $decision->lotSize,
            'entry_price' => $signal->entry,
            'stop_loss' => $signal->stop_loss,
            'take_profit' => $signal->take_profit,
            'status' => 'open',
            'opened_at' => now(),
        ]);

        Log::info('stetech.paper_trade_opened', ['trade_id' => $trade->id]);

        return $trade;
    }

    protected function placeLive(User $user, AiSignal $signal, RiskDecision $decision, ?\App\Models\BrokerAccount $brokerAccount): Trade
    {
        // Defense in depth: this check belongs here, not only in
        // RequireLiveTradingConfirmation middleware, because that
        // middleware is not guaranteed to sit in front of every path
        // that can reach ExecutionEngine (queued jobs, console commands,
        // future internal callers). This is the one place every live
        // order actually passes through, so this is where the kill
        // switch for the whole feature has to live.
        if (! filter_var(config('live_trading.enabled', false), FILTER_VALIDATE_BOOL)) {
            throw new \RuntimeException('LIVE_TRADING_ENABLED is not set to true -- refusing to place a live order regardless of broker_execution_mode.');
        }

        if (! $brokerAccount || $brokerAccount->trading_mode !== 'fully_automatic') {
            throw new \RuntimeException('Broker account is not authorized for fully automatic execution.');
        }

        $plan = $user->subscription?->plan;
        if ($plan && ! $plan->allowsTradingMode('fully_automatic')) {
            throw new \RuntimeException("The {$plan->name} plan does not allow fully automatic execution.");
        }

        $adapter = app(\App\Services\Execution\BrokerAdapterRegistry::class)->for($brokerAccount);
        $symbol = app(\App\Services\Execution\SymbolNormalizer::class)->toBroker($brokerAccount, $signal->instrument->symbol);
        $result=$adapter->placeOrder($brokerAccount,$symbol,$signal->direction,$decision->lotSize,$signal->stop_loss,$signal->take_profit);
        $brokerOrderId = data_get($result, 'orderCreateTransaction.id') ?? data_get($result, 'order_id') ?? data_get($result, 'positionId');
        return Trade::create(['user_id'=>$user->id,'ai_signal_id'=>$signal->id,'instrument_id'=>$signal->instrument_id,'side'=>$signal->direction,'mode'=>'live','lot_size'=>$decision->lotSize,'entry_price'=>$signal->entry,'stop_loss'=>$signal->stop_loss,'take_profit'=>$signal->take_profit,'status'=>'open','opened_at'=>now(),'broker_order_id'=>$brokerOrderId]);
    }

}
