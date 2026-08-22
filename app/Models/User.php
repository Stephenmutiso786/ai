<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $fillable = ['name', 'email', 'password', 'role', 'kyc_status'];

    protected $hidden = ['password', 'remember_token'];

    protected function casts(): array
    {
        return ['email_verified_at' => 'datetime'];
    }

    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    public function brokerAccounts()
    {
        return $this->hasMany(BrokerAccount::class);
    }

    public function riskProfile()
    {
        return $this->hasOne(RiskProfile::class);
    }

    public function subscription()
    {
        return $this->hasOne(Subscription::class)->latestOfMany();
    }

    public function trades()
    {
        return $this->hasMany(Trade::class);
    }

    protected static function booted(): void
    {
        // Every new client starts on the Demo plan automatically -- no
        // signup flow should ever leave a user without a subscription
        // row, since RunLimiter treats "no subscription" as zero access.
        static::created(function (self $user) {
            if ($user->role !== 'client') {
                return;
            }

            $demo = SubscriptionPlan::firstOrCreate(
                ['slug' => 'demo'],
                ['name' => 'Demo', 'price_usd_weekly' => null, 'runs_per_week' => null, 'total_runs_lifetime' => 1, 'is_demo' => true]
            );

            $user->subscription()->create([
                'subscription_plan_id' => $demo->id,
                'status' => 'active',
                'current_period_start' => now(),
                'current_period_end' => now()->addYears(10),
            ]);
        });
    }
}
