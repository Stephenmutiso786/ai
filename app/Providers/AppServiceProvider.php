<?php

namespace App\Providers;

use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use App\Services\Execution\OandaAdapter;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void {}

    public function boot(): void
    {
        Gate::before(fn ($user) => $user?->isSuperAdmin() ? true : null);

        foreach ([
            'view-admin-dashboard',
            'manage-users',
            'manage-plans',
            'manage-custom-requests',
            'manage-settings',
            'manage-ai-lab',
            'manage-operations',
            'manage-broker-certification',
            'manage-trading-workspace',
        ] as $permission) {
            Gate::define($permission, fn ($user) => $user->canPerform($permission));
        }

        Gate::define('access-admin', fn ($user) => $user->isAdmin());
        Gate::define('access-super-admin', fn ($user) => $user->isSuperAdmin());
    }
}
