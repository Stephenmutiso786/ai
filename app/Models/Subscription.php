<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Subscription extends Model
{
    protected $fillable = [
        'user_id', 'subscription_plan_id', 'status',
        'override_price_usd_weekly', 'override_runs_per_week', 'override_runs_unlimited',
        'runs_used_this_period', 'period_started_at',
        'current_period_start', 'current_period_end',
    ];

    protected function casts(): array
    {
        return [
            'override_runs_unlimited' => 'boolean',
            'period_started_at' => 'datetime',
            'current_period_start' => 'datetime',
            'current_period_end' => 'datetime',
        ];
    }

    public function plan()
    {
        return $this->belongsTo(SubscriptionPlan::class, 'subscription_plan_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function hasCustomTerms(): bool
    {
        return ! is_null($this->override_price_usd_weekly) || $this->override_runs_unlimited || ! is_null($this->override_runs_per_week);
    }

    public function effectivePriceUsdWeekly(): ?int
    {
        return $this->override_price_usd_weekly ?? $this->plan->price_usd_weekly;
    }

    /** Null return means unlimited. */
    public function effectiveRunsPerWeek(): ?int
    {
        if ($this->override_runs_unlimited) {
            return null;
        }

        return $this->override_runs_per_week ?? $this->plan->runs_per_week;
    }
}
