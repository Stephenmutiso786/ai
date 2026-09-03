<?php

use App\Http\Controllers\Admin\ControlCenterController;
use App\Http\Controllers\Admin\CustomPlanRequestController as AdminCustomPlanRequestController;
use App\Http\Controllers\Admin\PlanController as AdminPlanController;
use App\Http\Controllers\Admin\SettingsController as AdminSettingsController;
use App\Http\Controllers\Admin\UserController as AdminUserController;
use App\Http\Controllers\Admin\OperationsController;
use App\Http\Controllers\Admin\AiLabController;
use App\Http\Controllers\Admin\AiLabCallbackController;
use App\Http\Controllers\Admin\BrokerCertificationController;
use App\Http\Controllers\BrokerAccountController;
use App\Http\Controllers\CurrencyController;
use App\Http\Controllers\CustomPlanRequestController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\PricingController;
use App\Http\Controllers\SignalRunController;
use App\Http\Controllers\Internal\BrokerConnectorController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\TradingWorkspaceController;
use Illuminate\Support\Facades\Route;

Route::view('/', 'marketing.landing')->name('home');
Route::get('/pricing', [PricingController::class, 'index'])->name('pricing');
Route::post('/currency', [CurrencyController::class, 'set'])->name('currency.set');

Route::get('/payments/{plan}/checkout', [PaymentController::class, 'show'])->middleware('auth')->name('payments.show');
Route::post('/payments/checkout', [PaymentController::class, 'checkout'])->middleware('auth')->name('payments.checkout');
Route::post('/payments/mpesa/callback', [PaymentController::class, 'mpesaCallback'])->name('payments.mpesa.callback');
Route::post('/payments/stripe/webhook', [PaymentController::class, 'stripeWebhook'])->name('payments.stripe.webhook');
Route::get('/payments/stripe/success', [PaymentController::class, 'stripeSuccess'])->middleware('auth')->name('payments.stripe.success');
Route::get('/payments/cancelled', [PaymentController::class, 'cancelled'])->name('payments.cancelled');
Route::get('/payment/{subscription}/success', [PaymentController::class, 'success'])->middleware('auth')->name('payment.success');
Route::get('/payment/{subscription}/mpesa-waiting', [PaymentController::class, 'mpesaWaiting'])->middleware('auth')->name('payment.mpesa.waiting');
Route::get('/payment/{subscription}/status', [PaymentController::class, 'pollStatus'])->middleware('auth')->name('payment.mpesa.status');

Route::middleware(['auth', 'track.activity'])->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::post('/run', [SignalRunController::class, 'run'])->middleware('market.open')->name('run.trigger');
    Route::post('/dashboard/refresh-signals', [DashboardController::class, 'refreshSignals'])->middleware('market.open')->name('dashboard.refresh-signals');

    Route::get('/broker/connect', [BrokerAccountController::class, 'create'])->name('broker.connect');
    Route::post('/broker/connect', [BrokerAccountController::class, 'store'])->middleware('market.open')->name('broker.store');
    Route::get('/trading-workspace', [TradingWorkspaceController::class, 'index'])->name('trading.workspace');
    Route::get('/api/workspace/candles', [TradingWorkspaceController::class, 'candles'])->name('workspace.candles');
    Route::get('/api/workspace/analysis', [TradingWorkspaceController::class, 'analysis'])->name('workspace.analysis');
    Route::get('/api/workspace/health', [TradingWorkspaceController::class, 'health'])->name('workspace.health');
    Route::get('/api/workspace/symbol-map', [TradingWorkspaceController::class, 'symbolMap'])->name('workspace.symbol-map');
    Route::get('/api/workspace/latest-signal', [TradingWorkspaceController::class, 'latestSignal'])->name('workspace.latest-signal');
    Route::get('/api/workspace/positions', [TradingWorkspaceController::class, 'positions'])->name('workspace.positions');
    Route::get('/api/workspace/performance', [TradingWorkspaceController::class, 'performance'])->name('workspace.performance');

    Route::post('/broker/{account}/test', [BrokerConnectorController::class, 'test'])->middleware('market.open')->name('broker.test');
    Route::get('/broker/{account}/snapshot', [BrokerConnectorController::class, 'snapshot'])->middleware('market.open')->name('broker.snapshot');

    Route::get('/custom-package', [CustomPlanRequestController::class, 'create'])->name('custom-package.create');
    Route::post('/custom-package', [CustomPlanRequestController::class, 'store'])->name('custom-package.store');
});

Route::middleware(['auth', 'track.activity'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/', [ControlCenterController::class, 'index'])->name('control-center');
    Route::post('/emergency-stop', [ControlCenterController::class, 'emergencyStopAll'])->name('emergency-stop');

    Route::get('/ai-lab', [AiLabController::class, 'index'])->name('ai-lab.index');
    Route::get('/ai-lab/datasets', [AiLabController::class, 'datasets'])->name('ai-lab.datasets');
    Route::post('/ai-lab/datasets', [AiLabController::class, 'storeDataset'])->name('ai-lab.datasets.store');
    Route::get('/ai-lab/models', [AiLabController::class, 'models'])->name('ai-lab.models');
    Route::post('/ai-lab/models', [AiLabController::class, 'storeModel'])->name('ai-lab.models.store');
    Route::get('/ai-lab/jobs', [AiLabController::class, 'trainingJobs'])->name('ai-lab.jobs');
    Route::post('/ai-lab/training-runs', [AiLabController::class, 'train'])->name('ai-lab.train');
    Route::post('/ai-lab/backtests', [AiLabController::class, 'backtest'])->name('ai-lab.backtest');
    Route::post('/ai-lab/models/{model}/deploy', [AiLabController::class, 'deploy'])->name('ai-lab.models.deploy');
    Route::post('/ai-lab/diagnose', [AiLabController::class, 'diagnose'])->name('ai-lab.diagnose');
    Route::post('/ai-lab/load-latest-trained-model', [AiLabController::class, 'loadLatestTrainedModel'])->name('ai-lab.load-latest-trained-model');

    Route::get('/broker-certification', [BrokerCertificationController::class, 'index'])->name('broker-certification.index');
    Route::post('/broker-certification/{account}/run', [BrokerCertificationController::class, 'run'])->name('broker-certification.run');
    Route::post('/execution-failures/{failure}/resolve', [BrokerCertificationController::class, 'resolveFailure'])->name('execution-failures.resolve');

    Route::get('/operations', [OperationsController::class, 'index'])->name('operations.index');
    Route::post('/operations/check', [OperationsController::class, 'runCheck'])->name('operations.check');
    Route::post('/operations/incidents/{id}/acknowledge', [OperationsController::class, 'acknowledge'])->name('operations.ack');
    Route::post('/operations/incidents/{id}/resolve', [OperationsController::class, 'resolve'])->name('operations.resolve');

    Route::get('/settings', [AdminSettingsController::class, 'index'])->name('settings');
    Route::post('/settings', [AdminSettingsController::class, 'update'])->name('settings.update');

    Route::get('/users', [AdminUserController::class, 'index'])->name('users.index');
    Route::get('/users/{user}/edit', [AdminUserController::class, 'edit'])->name('users.edit');
    Route::put('/users/{user}', [AdminUserController::class, 'update'])->name('users.update');

    Route::get('/plans', [AdminPlanController::class, 'index'])->name('plans.index');
    Route::post('/plans', [AdminPlanController::class, 'store'])->name('plans.store');
    Route::put('/plans/{plan}', [AdminPlanController::class, 'update'])->name('plans.update');

    Route::get('/custom-requests', [AdminCustomPlanRequestController::class, 'index'])->name('custom-requests.index');
    Route::post('/custom-requests/{customPlanRequest}/approve', [AdminCustomPlanRequestController::class, 'approve'])->name('custom-requests.approve');
    Route::post('/custom-requests/{customPlanRequest}/reject', [AdminCustomPlanRequestController::class, 'reject'])->name('custom-requests.reject');
});


Route::middleware('internal.signature')->group(function () {
    Route::post('/internal/ai/training-runs/{run}/callback', [AiLabCallbackController::class, 'training'])->name('internal.ai.training.callback');
    Route::post('/internal/ai/backtests/{backtest}/callback', [AiLabCallbackController::class, 'backtest'])->name('internal.ai.backtest.callback');
});

require __DIR__.'/auth.php';

// Infrastructure health endpoint. Do not expose secrets.
Route::get('/health', function () {
    try { \Illuminate\Support\Facades\DB::select('select 1'); \Illuminate\Support\Facades\Redis::ping(); return response()->json(['status'=>'ok','service'=>'stetech-core','time'=>now()->toIso8601String()]); } catch (\Throwable $e) { return response()->json(['status'=>'degraded','service'=>'stetech-core'], 503); }
})->name('health');
