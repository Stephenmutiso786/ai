<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AiSignal extends Model
{
    protected $fillable = [
        'instrument_id', 'direction', 'confidence', 'entry', 'stop_loss',
        'take_profit', 'risk_reward', 'market_regime', 'reasoning', 'generated_at',
    ];

    protected function casts(): array
    {
        return ['generated_at' => 'datetime'];
    }

    public function instrument()
    {
        return $this->belongsTo(Instrument::class);
    }
}
