<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class ExecutionFailure extends Model {
    protected $fillable=['trade_id','broker_account_id','idempotency_key','stage','error','context','attempts','status','next_retry_at','resolved_at'];
    protected $casts=['context'=>'array','next_retry_at'=>'datetime','resolved_at'=>'datetime'];
    public function brokerAccount(){ return $this->belongsTo(BrokerAccount::class); }
    public function trade(){ return $this->belongsTo(Trade::class); }
}
