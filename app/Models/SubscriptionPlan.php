<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SubscriptionPlan extends Model
{
    protected $fillable = [
        'name', 'slug', 'price_usd_weekly', 'runs_per_week', 'total_runs_lifetime',
        'automation_allowed', 'broker_connections_limit', 'is_demo', 'is_custom_template', 'is_active',
    ];

    protected function casts(): array
    {
        return [
            'automation_allowed' => 'boolean',
            'broker_connections_limit' => 'integer',
            'is_demo' => 'boolean',
            'is_custom_template' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    public function isUnlimited(): bool
    {
        return is_null($this->runs_per_week) && is_null($this->total_runs_lifetime);
    }

    public function recommendedTradingMode(): string
    {
        if (! $this->automation_allowed) {
            return 'signals_only';
        }

        return $this->slug === 'pro' ? 'fully_automatic' : 'semi_automatic';
    }

    public function allowsTradingMode(string $mode): bool
    {
        return match ($this->recommendedTradingMode()) {
            'signals_only' => $mode === 'signals_only',
            'semi_automatic' => in_array($mode, ['signals_only', 'semi_automatic'], true),
            'fully_automatic' => in_array($mode, ['signals_only', 'semi_automatic', 'fully_automatic'], true),
            default => false,
        };
    }
}
