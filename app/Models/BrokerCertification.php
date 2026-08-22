<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class BrokerCertification extends Model {
    protected $fillable=['broker_account_id','status','checks','details','started_at','completed_at','certified_by'];
    protected $casts=['checks'=>'array','details'=>'array','started_at'=>'datetime','completed_at'=>'datetime'];
    public function brokerAccount(){ return $this->belongsTo(BrokerAccount::class); }
    public function certifier(){ return $this->belongsTo(User::class,'certified_by'); }
}
