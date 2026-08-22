<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DemoDeviceUsage extends Model
{
    protected $fillable = ['device_hash', 'user_id', 'ip_address', 'user_agent', 'used_at'];

    protected function casts(): array
    {
        return ['used_at' => 'datetime'];
    }
}
