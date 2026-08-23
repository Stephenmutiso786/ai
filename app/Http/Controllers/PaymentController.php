<?php
namespace App\Http\Controllers;
use App\Models\PaymentTransaction; use App\Models\Subscription; use App\Models\SubscriptionPlan; use App\Services\Payments\MpesaDarajaGateway; use App\Services\Payments\StripeGateway; use App\Services\Payments\SubscriptionActivator; use Illuminate\Http\Request; use Illuminate\Support\Facades\Auth; use Illuminate\Support\Str;
class PaymentController extends Controller {
 public function show(Request $r, SubscriptionPlan $plan){ abort_unless($plan->is_active && $plan->price_usd_weekly!==null,404); return view('payments.checkout', compact('plan')); }
 public function checkout(Request $r){ $data=$r->validate(['plan'=>'required|exists:subscription_plans,id','provider'=>'required|in:mpesa,stripe','phone'=>'nullable|string']); $plan=SubscriptionPlan::findOrFail($data['plan']); abort_unless($plan->is_active && $plan->price_usd_weekly!==null,422,'This plan cannot be purchased online.');
  $currency=$data['provider']==='mpesa'?'KES':'USD'; $rate=(float)(setting('payment_usd_to_kes_rate', 130)); if($rate<=0) abort(422,'USD to KES billing rate is not configured.'); $amount=$data['provider']==='mpesa' ? (int)round($plan->price_usd_weekly*$rate*100) : $plan->price_usd_weekly*100;
  $tx=PaymentTransaction::create(['user_id'=>Auth::id(),'subscription_plan_id'=>$plan->id,'provider'=>$data['provider'],'merchant_reference'=>'STX-'.Str::upper(Str::random(18)),'currency'=>$currency,'amount_minor'=>$amount,'metadata'=>['plan_slug'=>$plan->slug]]);
  $gateway=$data['provider']==='mpesa'?app(MpesaDarajaGateway::class):app(StripeGateway::class); $result=$gateway->create(Auth::user(),$tx,$data);
  if(isset($result['checkout_url'])) return redirect()->away($result['checkout_url']); return back()->with('status',$result['message']??'Payment initiated.')->with('payment_reference',$tx->merchant_reference);
 }
 public function mpesaCallback(Request $r, SubscriptionActivator $activator){ $body=$r->all(); $cb=$body['Body']['stkCallback']??[]; $ref=$cb['CheckoutRequestID']??null; if(!$ref) return response()->json(['ResultCode'=>1]); $tx=PaymentTransaction::where('provider','mpesa')->where('provider_reference',$ref)->first(); if(!$tx) return response()->json(['ResultCode'=>1]); if((int)($cb['ResultCode']??1)===0){$activator->activate($tx);} else {$tx->update(['status'=>'failed','metadata'=>array_merge($tx->metadata??[],['callback'=>$cb])]);} return response()->json(['ResultCode'=>0]); }
 public function stripeSuccess(Request $r, SubscriptionActivator $activator){$id=(string)$r->query('session_id'); $tx=PaymentTransaction::where('provider','stripe')->where('provider_reference',$id)->firstOrFail(); $result=app(StripeGateway::class)->verify($id); if($result['status']==='paid') $activator->activate($tx); return redirect()->route('dashboard')->with('status',$result['status']==='paid'?'Payment successful. Subscription activated.':'Payment is still being confirmed.');}

 public function stripeWebhook(Request $r, SubscriptionActivator $activator){
  $secret=setting('stripe_webhook_secret'); if(!$secret) return response()->json(['error'=>'webhook not configured'],503);
  $sig=(string)$r->header('Stripe-Signature'); $parts=[]; foreach(explode(',', $sig) as $part){[$k,$v]=array_pad(explode('=',trim($part),2),2,null); if($k&&$v)$parts[$k]=$v;}
  $payload=$r->getContent(); $expected=isset($parts['t'],$parts['v1']) ? hash_hmac('sha256',$parts['t'].'.'.$payload,$secret) : '';
  if(!$expected || !hash_equals($expected,$parts['v1'])) return response()->json(['error'=>'invalid signature'],400);
  $event=$r->json()->all(); if(($event['type']??'')==='checkout.session.completed' && (($event['data']['object']['payment_status']??'')==='paid')){ $id=$event['data']['object']['id']??null; $tx=PaymentTransaction::where('provider','stripe')->where('provider_reference',$id)->first(); if($tx) $activator->activate($tx); }
  return response()->json(['received'=>true]);
 }
 public function cancelled(){return redirect()->route('pricing')->with('run_error','Payment was cancelled.');}
 public function success(Subscription $subscription){ abort_if($subscription->user_id !== auth()->id(), 403); return view('payment.success', compact('subscription')); }
 public function mpesaWaiting(Subscription $subscription){ abort_if($subscription->user_id !== auth()->id(), 403); return view('payment.mpesa-waiting', compact('subscription')); }
 public function pollStatus(Subscription $subscription){ abort_if($subscription->user_id !== auth()->id(), 403); return response()->json(['status'=>$subscription->fresh()->status]); }
}
