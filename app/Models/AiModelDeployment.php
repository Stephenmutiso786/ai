<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class AiModelDeployment extends Model {protected $fillable=['ai_model_id','environment','status','config','deployed_at','rolled_back_at','deployed_by']; protected $casts=['config'=>'array','deployed_at'=>'datetime','rolled_back_at'=>'datetime']; public function model(){return $this->belongsTo(AiModel::class,'ai_model_id');}}
