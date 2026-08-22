<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class AiModel extends Model {
    protected $fillable=['name','version','framework','status','artifact_uri','metrics','parameters','notes','created_by'];
    protected $casts=['metrics'=>'array','parameters'=>'array'];
}
