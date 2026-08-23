<?php

namespace App\Services\Usage;

use App\Models\Subscription;
use App\Models\UsageRun;
use App\Models\User;
use Illuminate\Http\Request;

class RunLimiter
{
    public function __construct(protected DeviceFingerprint $fingerprint) {}

    public function check(User $user, ?Subscription $subscription, Request $request): RunCheckResult
    {
        if ($user->isSuperAdmin()) {
            return RunCheckResult::allow(null);
        }

        if (! $subscription) {
            return RunCheckResult::deny('No active subscription. Choose a plan to run the AI.');
        }

        $plan = $subscription->plan;

        $this->resetWindowIfElapsed($subscription);

        $limit = $subscription->effectiveRunsPerWeek(); // null = unlimited

        if ($limit !== null && $subscription->runs_used_this_period >= $limit) {
            return RunCheckResult::deny("Weekly limit reached ({$limit} runs/week on the {$plan->name} plan). Upgrade for more.");
        }

        $remaining = $limit === null ? null : max(0, $limit - $subscription->runs_used_this_period);

        return RunCheckResult::allow($remaining);
    }

    public function record(User $user, ?Subscription $subscription, Request $request, array $context = []): void
    {
        UsageRun::create([
            'user_id' => $user->id,
            'subscription_id' => $subscription?->id,
            'context' => $context,
            'ip_address' => $request->ip(),
        ]);

        if ($subscription && ! $user->isSuperAdmin()) {
            $subscription->increment('runs_used_this_period');
        }
    }

    protected function resetWindowIfElapsed(Subscription $subscription): void
    {
        $started = $subscription->period_started_at;

        if (! $started || $started->lt(now()->subWeek())) {
            $subscription->forceFill([
                'runs_used_this_period' => 0,
                'period_started_at' => now(),
            ])->save();
        }
    }
}

class RunCheckResult
{
    public function __construct(
        public bool $allowed,
        public ?string $reason = null,
        public ?int $remaining = null, // null = unlimited
    ) {}

    public static function allow(?int $remaining): self
    {
        return new self(true, null, $remaining);
    }

    public static function deny(string $reason): self
    {
        return new self(false, $reason);
    }
}
