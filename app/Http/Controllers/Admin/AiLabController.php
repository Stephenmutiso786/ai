<?php
namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AiBacktest;
use App\Models\AiDataset;
use App\Models\AiModel;
use App\Models\AiTrainingRun;
use App\Services\AI\AiLabClient;
use Illuminate\Http\Request;

class AiLabController extends Controller
{
    public function index(){
        $latestTrainingRun = AiTrainingRun::with(['model', 'dataset'])->latest('created_at')->first();
        $latestBacktest = AiBacktest::with('model')->latest('created_at')->first();
        return view('admin.ai-lab.index', [
            'stats'=>['models'=>AiModel::count(),'live'=>AiModel::where('status','live')->count(),'training'=>AiTrainingRun::whereIn('status',['queued','running'])->count(),'backtests'=>AiBacktest::where('status','completed')->count()],
            'models'=>AiModel::latest()->limit(20)->get(), 'runs'=>AiTrainingRun::with(['model','dataset'])->latest()->limit(15)->get(), 'backtests'=>AiBacktest::with('model')->latest()->limit(15)->get(),
            'latestTrainingRun' => $latestTrainingRun,
            'latestBacktest' => $latestBacktest,
        ]);
    }
    public function datasets(){ return view('admin.ai-lab.datasets',['datasets'=>AiDataset::latest()->paginate(25)]); }
    public function storeDataset(Request $r){ $d=$r->validate(['name'=>'required|string|max:150','provider'=>'nullable|string|max:100','instrument_symbol'=>'nullable|string|max:30','timeframe'=>'nullable|string|max:20','starts_at'=>'nullable|date','ends_at'=>'nullable|date','storage_uri'=>'nullable|string|max:500']); AiDataset::create($d+['created_by'=>auth()->id(),'status'=>'draft']); return back()->with('status','Dataset registered. Validate and ingest it through the data service before training.'); }
    public function models(){ return view('admin.ai-lab.models',['models'=>AiModel::latest()->paginate(25),'datasets'=>AiDataset::where('status','ready')->orderBy('name')->get()]); }
    public function storeModel(Request $r){ $d=$r->validate(['name'=>'required|string|max:150','version'=>'required|string|max:50','framework'=>'required|string|max:50','notes'=>'nullable|string']); $m=AiModel::create($d+['created_by'=>auth()->id(),'status'=>'draft']); return back()->with('status',"Model {$m->name} {$m->version} created."); }
    public function train(Request $r, AiLabClient $client){ $d=$r->validate(['ai_model_id'=>'required|exists:ai_models,id','ai_dataset_id'=>'nullable|exists:ai_datasets,id','config'=>'nullable|array']); $run=AiTrainingRun::create($d+['requested_by'=>auth()->id(),'status'=>'queued']); $client->startTraining($run); return back()->with('status','Training run queued.'); }
    public function backtest(Request $r, AiLabClient $client){ $d=$r->validate(['ai_model_id'=>'required|exists:ai_models,id','instrument_symbol'=>'required|string|max:30','timeframe'=>'required|string|max:20','starts_at'=>'required|date','ends_at'=>'required|date','config'=>'nullable|array']); $b=AiBacktest::create($d+['requested_by'=>auth()->id(),'status'=>'queued']); $client->startBacktest($b); return back()->with('status','Backtest queued.'); }
    public function deploy(Request $r, AiModel $model){ $r->validate(['mode'=>'required|in:paper,shadow,live']); if ($r->mode==='live') AiModel::where('status','live')->whereKeyNot($model->id)->update(['status'=>'approved']); $model->update(['status'=>$r->mode==='live'?'live':$r->mode]); return back()->with('status',"Model deployment state changed to {$model->fresh()->status}."); }
}
