<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SubscriptionPlan;
use App\Models\User;
use Illuminate\Http\Request;

class UserController extends Controller
{
    public function index()
    {
        $users = User::with(['subscription.plan', 'riskProfile'])->latest()->paginate(25);

        return view('admin.users', compact('users'));
    }

    public function edit(User $user)
    {
        $plans = SubscriptionPlan::where('is_active', true)->get();

        return view('admin.user-edit', compact('user', 'plans'));
    }

    public function update(Request $request, User $user)
    {
        $data = $request->validate([
            'role' => 'required|in:client,admin',
            'kyc_status' => 'required|in:pending,verified,rejected',
            'subscription_plan_id' => 'nullable|exists:subscription_plans,id',
            'trading_halted' => 'sometimes|boolean',
        ]);

        $user->update([
            'role' => $data['role'],
            'kyc_status' => $data['kyc_status'],
        ]);

        if (! empty($data['subscription_plan_id'])) {
            $user->subscription()->updateOrCreate(
                ['user_id' => $user->id],
                [
                    'subscription_plan_id' => $data['subscription_plan_id'],
                    'status' => 'active',
                    'current_period_start' => now(),
                    'current_period_end' => now()->addWeek(),
                ]
            );
        }

        if ($user->riskProfile) {
            $user->riskProfile->update(['trading_halted' => $request->boolean('trading_halted')]);
        }

        return redirect()->route('admin.users.index')->with('status', "Updated {$user->name}.");
    }
}
