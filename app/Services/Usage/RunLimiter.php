<?php

namespace App\Services\Usage;

use App\Models\DemoDeviceUsage;
use App\Models\Subscription;
use App\Models\UsageRun;
use App\Models\User;
use Illuminate\Http\Request;

class RunLimiter
{
    public function __construct(protected DeviceFingerprint $fingerprint) {}

    public function check(User $user, ?Subscription $subscription, Request $request): RunCheckResult
    {
        if (! $subscription) {
            return RunCheckResult::deny('No active subscription. Choose a plan to run the AI.');
        }

        $plan = $subscription->plan;

        if ($plan->is_demo) {
            return $this->checkDemo($user, $subscription, $request);
        }

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

        if ($subscription && ! $subscription->plan->is_demo) {
            $subscription->increment('runs_used_this_period');
        }

        if ($subscription && $subscription->plan->is_demo) {
            $hash = $this->fingerprint->resolve($request);
            DemoDeviceUsage::firstOrCreate(
                ['device_hash' => $hash],
                [
                    'user_id' => $user->id,
                    'ip_address' => $request->ip(),
                    'user_agent' => $request->userAgent(),
                    'used_at' => now(),
                ]
            );
        }
    }

    protected function checkDemo(User $user, Subscription $subscription, Request $request): RunCheckResult
    {
        $alreadyUsedByAccount = UsageRun::where('user_id', $user->id)
            ->where('subscription_id', $subscription->id)
            ->exists();

        if ($alreadyUsedByAccount) {
            return RunCheckResult::deny('Your demo run has already been used on this account. Upgrade to keep going.');
        }

        $hash = $this->fingerprint->resolve($request);
        if (DemoDeviceUsage::where('device_hash', $hash)->exists()) {
            return RunCheckResult::deny('This device has already used its one-time demo run. Upgrade to keep going.');
        }

        return RunCheckResult::allow(1);
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
