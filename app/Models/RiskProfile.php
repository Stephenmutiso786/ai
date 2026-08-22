<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RiskProfile extends Model
{
    protected $fillable = [
        'user_id', 'label', 'max_risk_per_trade_pct', 'max_daily_loss_pct',
        'max_weekly_loss_pct', 'max_open_positions', 'max_exposure_pct', 'max_slippage_bps', 'max_spread_bps', 'cooldown_seconds', 'trading_halted',
    ];

    protected function casts(): array
    {
        return ['trading_halted' => 'boolean'];
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
