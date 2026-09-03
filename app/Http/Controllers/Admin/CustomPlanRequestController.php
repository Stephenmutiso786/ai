<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CustomPlanRequest;
use App\Models\Subscription;
use App\Models\SubscriptionPlan;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CustomPlanRequestController extends Controller
{
    public function __construct()
    {
        $this->middleware('can:manage-custom-requests');
    }

    public function index()
    {
        $requests = CustomPlanRequest::with('user')->latest()->paginate(20);

        return view('admin.custom-requests', compact('requests'));
    }

    public function approve(Request $request, CustomPlanRequest $customPlanRequest)
    {
        $data = $request->validate([
            'approved_price_usd_weekly' => 'required|integer|min:0',
            'approved_runs_per_week' => 'nullable|integer|min:1|max:100000',
            'approved_runs_unlimited' => 'sometimes|boolean',
            'admin_notes' => 'nullable|string|max:2000',
        ]);

        $customPlanRequest->update([
            'status' => 'approved',
            'approved_price_usd_weekly' => $data['approved_price_usd_weekly'],
            'approved_runs_per_week' => $data['approved_runs_unlimited'] ?? false ? null : ($data['approved_runs_per_week'] ?? null),
            'approved_runs_unlimited' => $data['approved_runs_unlimited'] ?? false,
            'admin_notes' => $data['admin_notes'] ?? null,
            'reviewed_by' => Auth::id(),
            'reviewed_at' => now(),
        ]);

        $customPlan = SubscriptionPlan::firstOrCreate(
            ['slug' => 'custom'],
            ['name' => 'Custom', 'is_custom_template' => true, 'is_active' => false]
        );

        Subscription::updateOrCreate(
            ['user_id' => $customPlanRequest->user_id, 'subscription_plan_id' => $customPlan->id],
            [
                'status' => 'active',
                'override_price_usd_weekly' => $customPlanRequest->approved_price_usd_weekly,
                'override_runs_per_week' => $customPlanRequest->approved_runs_per_week,
                'override_runs_unlimited' => $customPlanRequest->approved_runs_unlimited,
                'current_period_start' => now(),
                'current_period_end' => now()->addWeek(),
            ]
        );

        return back()->with('status', 'Request approved and a custom subscription was created for the client.');
    }

    public function reject(Request $request, CustomPlanRequest $customPlanRequest)
    {
        $data = $request->validate(['admin_notes' => 'nullable|string|max:2000']);

        $customPlanRequest->update([
            'status' => 'rejected',
            'admin_notes' => $data['admin_notes'] ?? null,
            'reviewed_by' => Auth::id(),
            'reviewed_at' => now(),
        ]);

        return back()->with('status', 'Request rejected.');
    }
}
