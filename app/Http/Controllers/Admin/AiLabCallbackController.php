<?php
namespace App\Http\Controllers\Admin;
use App\Http\Controllers\Controller;
use App\Models\AiBacktest;
use App\Models\AiTrainingRun;
use Illuminate\Http\Request;

class AiLabCallbackController extends Controller {
    private function authorizeService(Request $r): void {
        $expected = (string) setting('ai_service_token');
        abort_if($expected === '' || !hash_equals($expected, (string) $r->bearerToken()), 401);
    }
    public function training(Request $r, AiTrainingRun $run){
        $this->authorizeService($r);
        $d=$r->validate(['status'=>'required|in:completed,failed','metrics'=>'nullable|array','error_message'=>'nullable|string']);
        $run->update(['status'=>$d['status'],'metrics'=>$d['metrics']??null,'error_message'=>$d['error_message']??null,'finished_at'=>now()]);
        if($d['status']==='completed') $run->model->update(['status'=>'trained','metrics'=>$d['metrics']??[]]);
        return response()->json(['ok'=>true]);
    }
    public function backtest(Request $r, AiBacktest $backtest){
        $this->authorizeService($r);
        $d=$r->validate(['status'=>'required|in:completed,failed','results'=>'nullable|array','error_message'=>'nullable|string']);
        $backtest->update(['status'=>$d['status'],'results'=>$d['results']??null,'error_message'=>$d['error_message']??null]);
        return response()->json(['ok'=>true]);
    }
}
