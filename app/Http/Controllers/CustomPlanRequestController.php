<?php

namespace App\Http\Controllers;

use App\Models\CustomPlanRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CustomPlanRequestController extends Controller
{
    public function create()
    {
        return view('marketing.custom-request');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'message' => 'required|string|max:2000',
            'requested_runs_per_week' => 'nullable|integer|min:1|max:100000',
        ]);

        CustomPlanRequest::create([
            'user_id' => Auth::id(),
            'message' => $data['message'],
            'requested_runs_per_week' => $data['requested_runs_per_week'] ?? null,
            'status' => 'pending',
        ]);

        return redirect()->route('dashboard')
            ->with('status', 'Your custom package request was sent. An admin will set a price and run allowance and follow up.');
    }
}
