<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SubscriptionPlan;
use Illuminate\Http\Request;

class PlanController extends Controller
{
    public function index()
    {
        $plans = SubscriptionPlan::where('is_custom_template', false)->orderBy('price_usd_weekly')->get();

        return view('admin.plans', compact('plans'));
    }

    public function update(Request $request, SubscriptionPlan $plan)
    {
        $data = $request->validate([
            'price_usd_weekly' => 'nullable|integer|min:0',
            'runs_per_week' => 'nullable|integer|min:1|max:100000',
            'broker_connections_limit' => 'nullable|integer|min:1|max:1000',
            'runs_unlimited' => 'sometimes|boolean',
            'is_active' => 'sometimes|boolean',
        ]);

        $plan->update([
            'price_usd_weekly' => $data['price_usd_weekly'] ?? null,
            'runs_per_week' => $request->boolean('runs_unlimited') ? null : ($data['runs_per_week'] ?? null),
            'broker_connections_limit' => $data['broker_connections_limit'] ?? null,
            'is_active' => $request->boolean('is_active'),
        ]);

        return back()->with('status', "Updated the {$plan->name} plan.");
    }
}
