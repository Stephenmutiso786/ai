<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class AiTrainingRun extends Model {
    protected $fillable=['ai_model_id','ai_dataset_id','status','job_reference','config','metrics','error_message','started_at','finished_at','requested_by'];
    protected $casts=['config'=>'array','metrics'=>'array','started_at'=>'datetime','finished_at'=>'datetime'];
    public function model(){ return $this->belongsTo(AiModel::class,'ai_model_id'); }
    public function dataset(){ return $this->belongsTo(AiDataset::class,'ai_dataset_id'); }
}
