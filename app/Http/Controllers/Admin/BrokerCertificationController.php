<?php
namespace App\Http\Controllers\Admin;
use App\Http\Controllers\Controller;
use App\Models\BrokerAccount;
use App\Models\BrokerCertification;
use App\Models\ExecutionFailure;
use App\Services\TradingOperations\BrokerCertificationService;
use Illuminate\Http\Request;
class BrokerCertificationController extends Controller {
    public function index(){ return view('admin.broker-certification.index',['accounts'=>BrokerAccount::latest()->paginate(25),'certifications'=>BrokerCertification::latest()->take(20)->get(),'failures'=>ExecutionFailure::whereIn('status',['open','retrying'])->latest()->take(50)->get()]); }
    public function run(Request $request,BrokerAccount $account,BrokerCertificationService $service){ $this->authorize('access-admin'); $service->certify($account,$request->user()->id); return back()->with('status','Broker certification completed.'); }
    public function resolveFailure(Request $request,ExecutionFailure $failure){ $failure->update(['status'=>'resolved','resolved_at'=>now()]); return back()->with('status','Execution failure resolved.'); }
}
