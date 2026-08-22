<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UsageRun extends Model
{
    public $timestamps = false;

    protected $fillable = ['user_id', 'subscription_id', 'context', 'ip_address', 'created_at'];

    protected function casts(): array
    {
        return ['context' => 'array', 'created_at' => 'datetime'];
    }

    protected static function booted(): void
    {
        static::creating(function (self $run) {
            $run->created_at ??= now();
        });
    }
}
