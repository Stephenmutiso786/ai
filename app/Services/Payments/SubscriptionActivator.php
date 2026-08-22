<?php
namespace App\Services\Payments;
use App\Models\PaymentTransaction; use App\Models\Subscription;
use Illuminate\Support\Facades\DB;
class SubscriptionActivator {
 public function activate(PaymentTransaction $tx): Subscription {
  return DB::transaction(function() use($tx){
   $tx->refresh(); if($tx->status==='paid') return Subscription::where('user_id',$tx->user_id)->latest('id')->firstOrFail();
   $tx->update(['status'=>'paid','paid_at'=>now()]); $sub=Subscription::where('user_id',$tx->user_id)->latest('id')->lockForUpdate()->first();
   $start=($sub && $sub->current_period_end && $sub->current_period_end->isFuture()) ? $sub->current_period_end : now();
   if(!$sub) $sub=new Subscription(['user_id'=>$tx->user_id]);
   $sub->subscription_plan_id=$tx->subscription_plan_id; $sub->status='active'; $sub->payment_provider=$tx->provider; $sub->current_period_start=$start; $sub->current_period_end=$start->copy()->addWeek(); $sub->runs_used_this_period=0; $sub->period_started_at=$start; $sub->save(); return $sub;
  });
 }
}
