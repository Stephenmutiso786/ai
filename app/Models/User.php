<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $fillable = [
        'name', 'email', 'password', 'role', 'kyc_status',
        'is_super_admin',
        'two_factor_secret', 'two_factor_recovery_codes', 'two_factor_confirmed_at',
    ];

    protected $hidden = [
        'password',
        'remember_token',
        'two_factor_secret',
        'two_factor_recovery_codes',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'two_factor_confirmed_at' => 'datetime',
        ];
    }

    public function isAdmin(): bool
    {
        return $this->role === 'admin' || $this->isSuperAdmin();
    }

    public function isSuperAdmin(): bool
    {
        return (bool) $this->is_super_admin;
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

    public function twoFactorEnabled(): bool
    {
        return ! empty($this->two_factor_secret) && ! empty($this->two_factor_confirmed_at);
    }

    protected static function booted(): void
    {
        // Every new client starts on a real plan automatically -- no
        // signup flow should ever leave a user without a subscription
        // row, since RunLimiter treats "no subscription" as zero access.
        static::created(function (self $user) {
            if ($user->role !== 'client') {
                return;
            }

            $plan = SubscriptionPlan::where('slug', 'basic')->where('is_active', true)->first()
                ?? SubscriptionPlan::where('is_active', true)->where('is_custom_template', false)->orderBy('price_usd_weekly')->first();

            if (! $plan) {
                return;
            }

            $user->subscription()->create([
                'subscription_plan_id' => $plan->id,
                'status' => 'active',
                'current_period_start' => now(),
                'current_period_end' => now()->addYears(10),
            ]);
        });
    }
}
