<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class PaymentTransaction extends Model {
 protected $fillable=['user_id','subscription_plan_id','provider','provider_reference','merchant_reference','currency','amount_minor','status','metadata','paid_at'];
 protected function casts(): array { return ['metadata'=>'array','paid_at'=>'datetime']; }
 public function user(){return $this->belongsTo(User::class);} public function plan(){return $this->belongsTo(SubscriptionPlan::class,'subscription_plan_id');}
}
