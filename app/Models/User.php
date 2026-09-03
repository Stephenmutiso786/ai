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
            'is_super_admin' => 'boolean',
        ];
    }

    public static function roleOptions(): array
    {
        return config('roles');
    }

    public function roleLabel(): string
    {
        return config("roles.{$this->role}.label", ucfirst(str_replace('_', ' ', (string) $this->role)));
    }

    public function permissions(): array
    {
        if ($this->role === 'super_admin' && ! $this->isSuperAdmin()) {
            return [];
        }

        $permissions = config("roles.{$this->role}.permissions", []);

        return array_values(array_filter($permissions, fn ($permission) => $permission !== '*' || $this->isSuperAdmin()));
    }

    public function canPerform(string $permission): bool
    {
        if ($this->isSuperAdmin()) {
            return true;
        }

        return in_array($permission, $this->permissions(), true);
    }

    public function isAdmin(): bool
    {
        return $this->isSuperAdmin() || $this->role === 'admin';
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
        //
    }
}
