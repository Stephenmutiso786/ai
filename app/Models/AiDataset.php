<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class AiDataset extends Model {
    protected $fillable=['name','provider','instrument_symbol','timeframe','starts_at','ends_at','row_count','storage_uri','status','metadata','created_by'];
    protected $casts=['starts_at'=>'datetime','ends_at'=>'datetime','metadata'=>'array'];
}
