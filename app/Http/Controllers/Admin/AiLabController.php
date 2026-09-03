<?php
namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AiBacktest;
use App\Models\AiDataset;
use App\Models\AiModel;
use App\Models\AiTrainingRun;
use App\Models\Setting;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\DB;
use App\Services\AI\AiLabClient;
use Illuminate\Http\Request;

class AiLabController extends Controller
{
    public function __construct()
    {
        $this->middleware('can:manage-ai-lab');
    }

    public function index(){
        $latestTrainingRun = AiTrainingRun::with(['model', 'dataset'])->latest('created_at')->first();
        $latestBacktest = AiBacktest::with('model')->latest('created_at')->first();
        $trainingCounts = [
            'queued' => AiTrainingRun::where('status', 'queued')->count(),
            'running' => AiTrainingRun::where('status', 'running')->count(),
            'completed' => AiTrainingRun::where('status', 'completed')->count(),
            'failed' => AiTrainingRun::where('status', 'failed')->count(),
        ];
        return view('admin.ai-lab.index', [
            'stats'=>['models'=>AiModel::count(),'live'=>AiModel::where('status','live')->count(),'training'=>AiTrainingRun::whereIn('status',['queued','running'])->count(),'backtests'=>AiBacktest::where('status','completed')->count()],
            'models'=>AiModel::latest()->limit(20)->get(), 'runs'=>AiTrainingRun::with(['model','dataset'])->latest()->limit(15)->get(), 'backtests'=>AiBacktest::with('model')->latest()->limit(15)->get(),
            'latestTrainingRun' => $latestTrainingRun,
            'latestBacktest' => $latestBacktest,
            'trainingCounts' => $trainingCounts,
        ]);
    }
    public function datasets(){ return view('admin.ai-lab.datasets',['datasets'=>AiDataset::latest()->paginate(25)]); }
    public function storeDataset(Request $r)
    {
        $d = $r->validate([
            'name' => 'required|string|max:150',
            'provider' => 'nullable|string|max:100',
            'instrument_symbol' => 'nullable|string|max:30',
            'timeframe' => 'nullable|string|max:20',
            'starts_at' => 'nullable|date',
            'ends_at' => 'nullable|date',
            'storage_uri' => 'nullable|string|max:500',
            'upload_file' => 'nullable|file|mimes:csv,txt|max:20480',
            'pasted_data' => 'nullable|string',
        ]);

        $storageUri = $d['storage_uri'] ?? null;
        $rowCount = null;
        $metadata = ['source' => 'manual'];

        if ($r->hasFile('upload_file')) {
            File::ensureDirectoryExists(storage_path('app/ai/datasets'));
            $uploaded = $r->file('upload_file');
            $storageUri = $uploaded->storeAs('ai/datasets', now()->format('YmdHis').'-'.$uploaded->getClientOriginalName());
            $storageUri = storage_path('app/'.$storageUri);
            $rowCount = $this->countCsvRows($storageUri);
            $metadata['upload_name'] = $uploaded->getClientOriginalName();
            $metadata['source'] = 'upload';
        } elseif (! empty(trim((string) ($d['pasted_data'] ?? '')))) {
            File::ensureDirectoryExists(storage_path('app/ai/datasets'));
            $storageUri = storage_path('app/ai/datasets/paste-'.now()->format('YmdHis').'.csv');
            file_put_contents($storageUri, $this->normalizePastedData($d['pasted_data']));
            $rowCount = $this->countCsvRows($storageUri);
            $metadata['source'] = 'paste';
        }

        AiDataset::create($d + [
            'row_count' => $rowCount,
            'storage_uri' => $storageUri,
            'metadata' => $metadata,
            'created_by' => auth()->id(),
            'status' => 'ready',
        ]);

        return back()->with('status', 'Dataset saved and ready for training.');
    }
    public function models(){ return view('admin.ai-lab.models',['models'=>AiModel::latest()->paginate(25),'datasets'=>AiDataset::where('status','ready')->orderBy('name')->get()]); }
    public function storeModel(Request $r){ $d=$r->validate(['name'=>'required|string|max:150','version'=>'required|string|max:50','framework'=>'required|string|max:50','notes'=>'nullable|string']); $m=AiModel::create($d+['created_by'=>auth()->id(),'status'=>'draft']); return back()->with('status',"Model {$m->name} {$m->version} created."); }
    public function train(Request $r, AiLabClient $client)
    {
        $d = $r->validate([
            'ai_model_id' => 'required|exists:ai_models,id',
            'ai_dataset_id' => 'nullable|exists:ai_datasets,id',
            'config' => 'nullable|array',
        ]);

        if (empty($d['ai_dataset_id'])) {
            $symbol = strtoupper((string) data_get($d, 'config.local_symbol', ''));
            $timeframe = strtoupper((string) data_get($d, 'config.local_timeframe', ''));
            if ($symbol && $timeframe) {
                $rows = DB::table('market_data_candles')
                    ->where('symbol', $symbol)
                    ->where('timeframe', $timeframe)
                    ->orderBy('time')
                    ->get(['time', 'open', 'high', 'low', 'close', 'volume']);

                if ($rows->isNotEmpty()) {
                    File::ensureDirectoryExists(storage_path('app/ai/datasets'));
                    $uri = storage_path('app/ai/datasets/'.strtolower($symbol).'-'.strtolower($timeframe).'-'.now()->format('YmdHis').'.csv');
                    $fp = fopen($uri, 'w');
                    fwrite($fp, "timestamp,open,high,low,close,volume\n");
                    foreach ($rows as $row) {
                        fwrite($fp, implode(',', [
                            optional($row->time)->format('Y-m-d H:i:s'),
                            $row->open,
                            $row->high,
                            $row->low,
                            $row->close,
                            $row->volume ?? '',
                        ])."\n");
                    }
                    fclose($fp);

                    $dataset = \App\Models\AiDataset::create([
                        'name' => "{$symbol} {$timeframe} local dataset",
                        'provider' => 'local',
                        'instrument_symbol' => $symbol,
                        'timeframe' => $timeframe,
                        'row_count' => $rows->count(),
                        'storage_uri' => $uri,
                        'status' => 'ready',
                        'metadata' => ['source' => 'market_data_candles', 'created_via' => 'admin-train'],
                        'created_by' => auth()->id(),
                    ]);
                    $d['ai_dataset_id'] = $dataset->id;
                }
            }
        }

        $run = AiTrainingRun::create($d + ['requested_by' => auth()->id(), 'status' => 'queued']);
        $client->startTraining($run);
        return back()->with('status', 'Training run queued.');
    }
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

    public function trainingJobs()
    {
        return view('admin.ai-lab.jobs', [
            'runs' => AiTrainingRun::with(['model', 'dataset'])->latest()->paginate(30),
            'trainingCounts' => [
                'queued' => AiTrainingRun::where('status', 'queued')->count(),
                'running' => AiTrainingRun::where('status', 'running')->count(),
                'completed' => AiTrainingRun::where('status', 'completed')->count(),
                'failed' => AiTrainingRun::where('status', 'failed')->count(),
            ],
            'latestHealthChecks' => DB::table('system_health_checks')->orderByDesc('checked_at')->limit(10)->get(),
        ]);
    }

    private function normalizePastedData(string $input): string
    {
        $text = trim($input);
        $text = str_replace(["\r\n", "\r"], "\n", $text);

        if (! str_contains($text, "\n")) {
            return $text;
        }

        $lines = array_values(array_filter(array_map('trim', explode("\n", $text))));
        $first = strtolower($lines[0] ?? '');
        $headerLooksValid = str_contains($first, 'timestamp') || str_contains($first, 'open') || str_contains($first, 'close');

        if (! $headerLooksValid) {
            array_unshift($lines, 'timestamp,open,high,low,close,volume');
        }

        return implode("\n", $lines)."\n";
    }

    private function countCsvRows(string $path): int
    {
        if (! File::exists($path)) {
            return 0;
        }

        $rows = array_filter(file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES));

        return max(count($rows) - 1, 0);
    }
}
