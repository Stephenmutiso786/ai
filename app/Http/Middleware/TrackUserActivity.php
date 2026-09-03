<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\View;

class TrackUserActivity
{
    public function handle(Request $request, Closure $next)
    {
        $user = $request->user();

        if ($user) {
            if (! $user->last_seen_at || $user->last_seen_at->lt(now()->subSeconds(30))) {
                $user->forceFill(['last_seen_at' => now()])->saveQuietly();
            }

            View::share('onlineUsers', User::query()
                ->where('last_seen_at', '>=', now()->subMinutes(5))
                ->orderBy('name')
                ->get(['id', 'name', 'role', 'is_super_admin', 'last_seen_at']));
        }

        return $next($request);
    }
}
