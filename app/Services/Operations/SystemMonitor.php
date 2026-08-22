<?php
namespace App\Services\Operations;

use App\Models\Setting;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Log;

class SystemMonitor
{
    public function __construct(private OperationsNotifier $notifier) {}
    public function run(): array {
        $checks = [];
        $checks[] = $this->check('database', fn()=>DB::select('select 1'));
        $checks[] = $this->check('redis', fn()=>Redis::ping());
        $ai = Setting::getValue('ai_service_url');
        if ($ai) $checks[] = $this->check('ai_service', fn()=>Http::timeout(5)->get(rtrim($ai,'/').'/health')->throw()->json());
        $overall = collect($checks)->contains(fn($c)=>$c['status'] === 'critical') ? 'critical' : (collect($checks)->contains(fn($c)=>$c['status'] === 'degraded') ? 'degraded' : 'ok');
        return ['status'=>$overall,'checks'=>$checks];
    }
    private function check(string $component, callable $callback): array {
        $start = hrtime(true); $status='ok'; $message='healthy'; $meta=[];
        try { $result=$callback(); $meta=is_array($result)?$result:[]; }
        catch (\Throwable $e) { $status='critical'; $message=$e->getMessage(); }
        $latency=(int)((hrtime(true)-$start)/1_000_000);
        DB::table('system_health_checks')->insert(['component'=>$component,'status'=>$status,'latency_ms'=>$latency,'message'=>$message,'meta'=>json_encode($meta),'checked_at'=>now(),'created_at'=>now(),'updated_at'=>now()]);
        if ($status !== 'ok') $this->openIncident($component, $status, "{$component} health check failed", $message, ['latency_ms'=>$latency]);
        return compact('component','status','latency','message');
    }
    public function openIncident(string $component,string $severity,string $title,?string $details,array $context=[]): void {
        $fingerprint=hash('sha256', strtolower($component.'|'.$title));
        $existing=DB::table('system_incidents')->where('fingerprint',$fingerprint)->where('status','open')->first();
        if ($existing) { DB::table('system_incidents')->where('id',$existing->id)->update(['last_seen_at'=>now(),'details'=>$details,'context'=>json_encode($context),'updated_at'=>now()]); return; }
        $id=DB::table('system_incidents')->insertGetId(['fingerprint'=>$fingerprint,'component'=>$component,'severity'=>$severity,'status'=>'open','title'=>$title,'details'=>$details,'context'=>json_encode($context),'first_seen_at'=>now(),'last_seen_at'=>now(),'created_at'=>now(),'updated_at'=>now()]);
        $incident=(array)DB::table('system_incidents')->find($id); $this->notifier->incident($incident); Log::critical($title, $context);
    }
}
