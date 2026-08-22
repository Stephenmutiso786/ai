<?php
namespace App\Http\Controllers;
use App\Models\Instrument; use App\Models\Trade; use Illuminate\Http\Request; use Illuminate\Support\Facades\DB;
class TradingWorkspaceController extends Controller {
 public function index(Request $r){$user=$r->user();$instruments=Instrument::orderBy('symbol')->get();$symbol=$r->string('symbol')->toString() ?: optional($instruments->first())->symbol;return view('trading-workspace.index',compact('instruments','symbol'));}
 public function candles(Request $r){$symbol=$r->validate(['symbol'=>'required|string|max:32'])['symbol'];$limit=min(max((int)$r->input('limit',500),50),2000);$rows=DB::table('market_data_candles')->where('symbol',$symbol)->orderByDesc('time')->limit($limit)->get()->reverse()->values()->map(fn($c)=>['time'=>strtotime($c->time),'open'=>(float)$c->open,'high'=>(float)$c->high,'low'=>(float)$c->low,'close'=>(float)$c->close]);return response()->json(['symbol'=>$symbol,'candles'=>$rows]);}
 public function positions(Request $r){return response()->json(Trade::with('instrument')->where('user_id',$r->user()->id)->where('status','open')->latest('opened_at')->get());}
 public function performance(Request $r){$t=Trade::where('user_id',$r->user()->id)->where('status','closed')->orderBy('closed_at')->get();$equity=0;$curve=[];foreach($t as $x){$equity+=(float)($x->profit_loss??0);$curve[]=['time'=>optional($x->closed_at)->timestamp,'value'=>$equity];}return response()->json(['curve'=>$curve,'net_profit'=>$equity,'trades'=>$t->count()]);}
}
