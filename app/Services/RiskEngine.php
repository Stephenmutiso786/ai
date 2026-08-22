<?php
namespace App\Services;

use App\Models\AiSignal;
use App\Models\BrokerAccount;
use App\Models\RiskProfile;
use App\Models\Trade;
use App\Models\User;
use App\Services\Execution\BrokerAdapterRegistry;
use Illuminate\Support\Carbon;

/**
 * Production portfolio risk gate. The broker is the source of truth for
 * account state and contract specifications. Missing safety data rejects.
 */
class RiskEngine
{
    public function evaluate(User $user, AiSignal $signal, ?BrokerAccount $account = null): RiskDecision
    {
        $profile = $user->riskProfile ?? RiskProfile::create(['user_id' => $user->id]);
        if ($profile->trading_halted) return RiskDecision::reject('Trading halted for this account.');
        if ($signal->direction === 'wait') return RiskDecision::reject('AI model returned WAIT.');
        if (!$signal->entry || !$signal->stop_loss) return RiskDecision::reject('Signal has no valid entry/stop-loss.');
        if ($signal->confidence < 60) return RiskDecision::reject("Confidence {$signal->confidence}% below execution threshold.");

        $account ??= $user->brokerAccounts()->where('connection_status','connected')->first();
        if (!$account) return RiskDecision::reject('No connected broker account is available.');

        $adapter = app(BrokerAdapterRegistry::class)->for($account);
        $snapshot = $adapter->accountSnapshot($account);
        $equity = (float) data_get($snapshot, 'equity', data_get($snapshot, 'account.equity', 0));
        $freeMargin = (float) data_get(
            $snapshot,
            'free_margin',
            data_get($snapshot, 'margin_available', data_get($snapshot, 'account.free_margin', 0))
        );
        if ($equity <= 0 || $freeMargin < 0) return RiskDecision::reject('Live broker equity or margin is unavailable.');

        $open = Trade::where('user_id',$user->id)->where('broker_account_id',$account->id)->where('status','open')->get();
        if ($open->count() >= (int)$profile->max_open_positions) return RiskDecision::reject('Maximum open positions reached.');

        $todayLoss = abs((float) Trade::where('user_id',$user->id)->where('broker_account_id',$account->id)
            ->where('closed_at','>=',Carbon::now()->startOfDay())->where('profit_loss','<',0)->sum('profit_loss'));
        if ($equity > 0 && ($todayLoss / $equity * 100) >= (float)$profile->max_daily_loss_pct) return RiskDecision::reject('Daily loss limit reached.');

        $entry = (float)$signal->entry; $stop = (float)$signal->stop_loss;
        $stopDistance = abs($entry-$stop);
        if ($stopDistance <= 0) return RiskDecision::reject('Invalid stop-loss distance.');

        // Contract specifications must be fetched for the exact instrument being traded.
        // Account snapshots are not a safe source of symbol-specific contract data.
        $symbol = $signal->instrument?->symbol;
        if (! $symbol) return RiskDecision::reject('Signal instrument is unavailable.');
        try {
            $spec = (array) $adapter->getInstrumentSpecifications($account, $symbol);
        } catch (\Throwable $e) {
            return RiskDecision::reject('Live broker contract specifications are unavailable: '.$e->getMessage());
        }
        $contractSize = (float)data_get($spec,'contract_size', data_get($spec,'contractSize',0));
        $minLot = (float)data_get($spec,'min_lot',0);
        $maxLot = (float)data_get($spec,'max_lot',0);
        $lotStep = (float)data_get($spec,'lot_step',0);
        $marginPerLot = (float)data_get($spec,'margin_per_lot',0);
        if ($contractSize<=0 || $minLot<=0 || $maxLot<=0 || $lotStep<=0) return RiskDecision::reject('Live broker contract specifications are unavailable.');

        $riskMoney = $equity * ((float)$profile->max_risk_per_trade_pct / 100.0);
        $lot = $riskMoney / ($stopDistance * $contractSize);
        $lot = floor($lot / $lotStep) * $lotStep;
        $lot = min($lot,$maxLot);
        if ($lot < $minLot) return RiskDecision::reject('Calculated position is below the broker minimum lot size.');
        if (!is_finite($lot) || $lot<=0) return RiskDecision::reject('Position size could not be calculated from live broker data.');

        $estimatedMargin = $marginPerLot > 0 ? $marginPerLot*$lot : 0;
        if ($estimatedMargin > 0 && $estimatedMargin > $freeMargin*0.80) return RiskDecision::reject('Insufficient free margin after safety buffer.');

        $existingExposure = (float)$open->sum(fn($t)=>(float)$t->lot_size);
        $newExposurePct = ($existingExposure + $lot) * $contractSize * $entry / max($equity,1) * 100;
        if ($newExposurePct > (float)$profile->max_exposure_pct) return RiskDecision::reject('Maximum account exposure would be exceeded.');

        return RiskDecision::approve(round($lot, 4), $riskMoney, $estimatedMargin);
    }
}
