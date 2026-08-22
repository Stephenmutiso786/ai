<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Instrument extends Model
{
    protected $fillable = ['symbol', 'category', 'is_active'];

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    public function latestSignal()
    {
        return $this->hasOne(AiSignal::class)->latestOfMany('generated_at');
    }
}
