<?php

namespace App\Http\Controllers;

use App\Models\SubscriptionPlan;

class PricingController extends Controller
{
    public function index()
    {
        $plans = SubscriptionPlan::where('is_active', true)->orderBy('price_kes_weekly')->get();

        return view('marketing.pricing', compact('plans'));
    }
}
