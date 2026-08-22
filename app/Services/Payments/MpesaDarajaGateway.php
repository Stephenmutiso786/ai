<?php
namespace App\Services\Payments;
use App\Models\PaymentTransaction; use App\Models\User; use App\Models\Setting; use Illuminate\Support\Facades\Http; use Illuminate\Support\Str;
class MpesaDarajaGateway implements PaymentGateway {
 private function base(): string { return rtrim(setting('mpesa_base_url','https://api.safaricom.co.ke'),'/'); }
 private function token(): string {
  $key=setting('mpesa_consumer_key'); $secret=setting('mpesa_consumer_secret');
  if(!$key||!$secret) throw new \RuntimeException('M-Pesa API credentials are not configured.');
  $r=Http::timeout(20)->withBasicAuth($key,$secret)->get($this->base().'/oauth/v1/generate?grant_type=client_credentials'); $r->throw(); return (string)$r->json('access_token');
 }
 public function create(User $user, PaymentTransaction $tx, array $payload): array {
  $shortcode=setting('mpesa_shortcode'); $passkey=setting('mpesa_passkey'); $callback=rtrim(config('app.url'),'/').'/payments/mpesa/callback';
  if(!$shortcode||!$passkey) throw new \RuntimeException('M-Pesa shortcode/passkey not configured.');
  $phone=preg_replace('/\D/','',(string)($payload['phone']??'')); if(str_starts_with($phone,'0')) $phone='254'.substr($phone,1); if(!str_starts_with($phone,'254')) throw new \InvalidArgumentException('Use a valid Kenyan phone number.');
  $timestamp=now()->format('YmdHis'); $password=base64_encode($shortcode.$passkey.$timestamp);
  $amount=max(1,(int)ceil($tx->amount_minor/100));
  $body=['BusinessShortCode'=>$shortcode,'Password'=>$password,'Timestamp'=>$timestamp,'TransactionType'=>'CustomerPayBillOnline','Amount'=>$amount,'PartyA'=>$phone,'PartyB'=>$shortcode,'PhoneNumber'=>$phone,'CallBackURL'=>$callback,'AccountReference'=>$tx->merchant_reference,'TransactionDesc'=>'STETECH weekly subscription'];
  $r=Http::timeout(25)->withToken($this->token())->post($this->base().'/mpesa/stkpush/v1/processrequest',$body); $r->throw(); $data=$r->json();
  $tx->update(['provider_reference'=>$data['CheckoutRequestID']??null,'status'=>'processing','metadata'=>array_merge($tx->metadata??[],['mpesa'=>$data,'phone'=>$phone])]);
  return ['status'=>'processing','reference'=>$data['CheckoutRequestID']??null,'message'=>$data['CustomerMessage']??'Check your phone to complete payment.'];
 }
 public function verify(string $reference): array { return ['status'=>'processing','reference'=>$reference]; }
}
