<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class AiBacktest extends Model {
    protected $fillable=['ai_model_id','instrument_symbol','timeframe','starts_at','ends_at','status','config','results','error_message','requested_by'];
    protected $casts=['starts_at'=>'datetime','ends_at'=>'datetime','config'=>'array','results'=>'array'];
    public function model(){ return $this->belongsTo(AiModel::class,'ai_model_id'); }
}
