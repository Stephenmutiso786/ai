<?php
namespace App\Services\Payments;
use App\Models\PaymentTransaction; use App\Models\User; use App\Models\Setting; use Illuminate\Support\Facades\Http;
class StripeGateway implements PaymentGateway {
 private function key(): string { $k=setting('stripe_secret_key'); if(!$k) throw new \RuntimeException('Stripe secret key is not configured.'); return $k; }
 public function create(User $user, PaymentTransaction $tx, array $payload): array {
  $r=Http::timeout(25)->withToken($this->key())->asForm()->post('https://api.stripe.com/v1/checkout/sessions',[
   'mode'=>'payment','success_url'=>rtrim(config('app.url'),'/').'/payments/stripe/success?session_id={CHECKOUT_SESSION_ID}','cancel_url'=>rtrim(config('app.url'),'/').'/payments/cancelled','client_reference_id'=>$tx->merchant_reference,'customer_email'=>$user->email,
   'line_items[0][price_data][currency]'=>strtolower($tx->currency),'line_items[0][price_data][product_data][name]'=>'STETECH weekly subscription','line_items[0][price_data][unit_amount]'=>$tx->amount_minor,'line_items[0][quantity]'=>1,
  ]); $r->throw(); $d=$r->json(); $tx->update(['provider_reference'=>$d['id']??null,'metadata'=>array_merge($tx->metadata??[],['stripe'=>$d])]); return ['status'=>'pending','checkout_url'=>$d['url']??null,'reference'=>$d['id']??null];
 }
 public function verify(string $reference): array { $r=Http::timeout(20)->withToken($this->key())->get('https://api.stripe.com/v1/checkout/sessions/'.$reference); $r->throw(); $d=$r->json(); return ['status'=>($d['payment_status']??'')==='paid'?'paid':'pending','data'=>$d]; }
}
