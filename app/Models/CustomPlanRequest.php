<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CustomPlanRequest extends Model
{
    protected $fillable = [
        'user_id', 'message', 'requested_runs_per_week', 'status',
        'approved_price_usd_weekly', 'approved_runs_per_week', 'approved_runs_unlimited',
        'admin_notes', 'reviewed_by', 'reviewed_at',
    ];

    protected function casts(): array
    {
        return [
            'approved_runs_unlimited' => 'boolean',
            'reviewed_at' => 'datetime',
        ];
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function reviewer()
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }
}
