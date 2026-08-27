<?php
namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AiBacktest;
use App\Models\AiDataset;
use App\Models\AiModel;
use App\Models\AiTrainingRun;
use App\Models\Setting;
use Illuminate\Support\Facades\File;
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
    public function diagnose()
    {
        $issues = [];

        foreach (['ai_service_url', 'ai_service_token', 'ai_market_data_provider'] as $key) {
            if (! Setting::getValue($key)) {
                $issues[] = "Missing setting: {$key}";
            }
        }

        $provider = strtolower((string) (Setting::getValue('ai_market_data_provider') ?: 'oanda'));
        if ($provider === 'oanda') {
            foreach (['oanda_api_token', 'oanda_account_id'] as $key) {
                if (! Setting::getValue($key)) {
                    $issues[] = "Missing OANDA setting: {$key}";
                }
            }
        } elseif (in_array($provider, ['twelve', 'twelvedata', 'twelve_data'], true)) {
            if (! Setting::getValue('twelve_data_api_key') && ! Setting::getValue('market_data_api_key')) {
                $issues[] = 'Missing Twelve Data / market data API key.';
            }
        } else {
            $issues[] = "Unsupported AI market-data provider: {$provider}";
        }

        $latestRun = AiTrainingRun::latest('created_at')->first();
        $latestSignal = \App\Models\AiSignal::latest('generated_at')->first();
        $latestBacktest = AiBacktest::latest('created_at')->first();

        return back()->with([
            'status' => empty($issues) ? 'AI diagnostics completed. No configuration gaps found.' : 'AI diagnostics found configuration gaps.',
            'ai_diag_admin' => [
                'issues' => $issues,
                'latest_run' => $latestRun?->status,
                'latest_signal' => $latestSignal?->generated_at?->diffForHumans(),
                'latest_backtest' => $latestBacktest?->status,
            ],
        ]);
    }

    public function loadLatestTrainedModel()
    {
        $artifactPath = base_path('ai-service/artifacts/model-eurusd-multitimeframe.joblib');

        if (! File::exists($artifactPath)) {
            return back()->with('status', 'No trained artifact found yet. Train the model first.');
        }

        $model = AiModel::where('status', 'live')->latest('updated_at')->first()
            ?? AiModel::firstOrCreate(
                ['name' => 'STETECH Core'],
                ['version' => '1.0.0', 'framework' => 'fallback', 'status' => 'draft']
            );

        $model->forceFill([
            'status' => 'live',
            'framework' => 'trained-local',
            'artifact_uri' => $artifactPath,
            'metrics' => [
                'source' => 'local_csv_training',
                'artifact' => basename($artifactPath),
                'loaded_at' => now()->toIso8601String(),
            ],
        ])->save();

        return back()->with('status', "Loaded trained artifact into {$model->name} {$model->version}.");
    }
}
