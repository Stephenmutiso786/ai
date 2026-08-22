<?php
namespace App\Http\Middleware;
use Closure; use Illuminate\Http\Request;
class EnsureActiveSubscription { public function handle(Request $request, Closure $next){$sub=$request->user()?->subscription; if(!$sub || $sub->status!=='active' || ($sub->current_period_end && $sub->current_period_end->isPast())) return redirect()->route('pricing')->with('run_error','An active subscription is required.'); return $next($request);} }
