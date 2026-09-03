<?php
namespace App\Http\Controllers\Admin;
use App\Http\Controllers\Controller;
use App\Services\Operations\SystemMonitor;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
class OperationsController extends Controller {
 public function __construct(){ $this->middleware('can:manage-operations'); }
 public function index(){ return view('admin.operations',['incidents'=>DB::table('system_incidents')->orderByDesc('last_seen_at')->limit(50)->get(),'checks'=>DB::table('system_health_checks')->orderByDesc('checked_at')->limit(50)->get(),'backups'=>DB::table('backup_runs')->orderByDesc('started_at')->limit(20)->get()]); }
 public function acknowledge(Request $r,int $id){ DB::table('system_incidents')->where('id',$id)->update(['status'=>'acknowledged','acknowledged_at'=>now(),'acknowledged_by'=>Auth::id(),'updated_at'=>now()]); return back()->with('status','Incident acknowledged.'); }
 public function resolve(int $id){ DB::table('system_incidents')->where('id',$id)->update(['status'=>'resolved','resolved_at'=>now(),'updated_at'=>now()]); return back()->with('status','Incident resolved.'); }
 public function runCheck(SystemMonitor $monitor){ $monitor->run(); return back()->with('status','Health checks completed.'); }
}
