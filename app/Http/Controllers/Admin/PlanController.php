<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SubscriptionPlan;
use Illuminate\Http\Request;

class PlanController extends Controller
{
    public function __construct()
    {
        $this->middleware('can:manage-plans');
    }

    public function index()
    {
        $plans = SubscriptionPlan::where('is_custom_template', false)->orderBy('price_usd_weekly')->get();

        return view('admin.plans', compact('plans'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:80',
            'slug' => 'required|string|max:80|alpha_dash|unique:subscription_plans,slug',
            'price_usd_weekly' => 'nullable|integer|min:0',
            'runs_per_week' => 'nullable|integer|min:1|max:100000',
            'broker_connections_limit' => 'nullable|integer|min:1|max:1000',
            'automation_allowed' => 'sometimes|boolean',
            'is_active' => 'sometimes|boolean',
        ]);

        SubscriptionPlan::create([
            'name' => $data['name'],
            'slug' => $data['slug'],
            'price_usd_weekly' => $data['price_usd_weekly'] ?? null,
            'runs_per_week' => $data['runs_per_week'] ?? null,
            'broker_connections_limit' => $data['broker_connections_limit'] ?? null,
            'automation_allowed' => $request->boolean('automation_allowed'),
            'is_active' => $request->boolean('is_active', true),
        ]);

        return back()->with('status', "Created the {$data['name']} plan.");
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
