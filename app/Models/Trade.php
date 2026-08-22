<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Trade extends Model
{
    protected $fillable = [
        'user_id', 'broker_account_id', 'ai_signal_id', 'instrument_id', 'side', 'mode',
        'lot_size', 'entry_price', 'stop_loss', 'take_profit', 'close_price', 'profit_loss',
        'status', 'rejection_reason', 'opened_at', 'closed_at',
    ];

    protected function casts(): array
    {
        return ['opened_at' => 'datetime', 'closed_at' => 'datetime'];
    }

    public function instrument()
    {
        return $this->belongsTo(Instrument::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
