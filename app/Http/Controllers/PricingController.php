<?php

namespace App\Http\Controllers;

use App\Models\SubscriptionPlan;

class PricingController extends Controller
{
    public function index()
    {
        $plans = SubscriptionPlan::where('is_active', true)->orderByRaw('price_usd_weekly is null, price_usd_weekly asc')->get();

        return view('marketing.pricing', compact('plans'));
    }
}
