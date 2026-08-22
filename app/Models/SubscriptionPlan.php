<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SubscriptionPlan extends Model
{
    protected $fillable = [
        'name', 'slug', 'price_usd_weekly', 'runs_per_week', 'total_runs_lifetime',
        'automation_allowed', 'is_demo', 'is_custom_template', 'is_active',
    ];

    protected function casts(): array
    {
        return [
            'automation_allowed' => 'boolean',
            'is_demo' => 'boolean',
            'is_custom_template' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    public function isUnlimited(): bool
    {
        return is_null($this->runs_per_week) && is_null($this->total_runs_lifetime);
    }
}
