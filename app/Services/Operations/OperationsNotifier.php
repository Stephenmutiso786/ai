<?php
namespace App\Services\Operations;

use App\Models\Setting;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class OperationsNotifier
{
    public function incident(array $incident): void
    {
        $payload = ['type'=>'system_incident','application'=>config('app.name'),'incident'=>$incident,'occurred_at'=>now()->toIso8601String()];
        $webhook = Setting::getValue('ops_alert_webhook_url');
        if ($webhook) {
            try { Http::timeout(10)->post($webhook, $payload)->throw(); } catch (\Throwable $e) { Log::error('Operations webhook failed', ['error'=>$e->getMessage()]); }
        }
        $email = Setting::getValue('ops_alert_email');
        if ($email) {
            try { Mail::raw("[{$incident['severity']}] {$incident['title']}\n\n".($incident['details'] ?? ''), fn($m) => $m->to($email)->subject('STETECH Operations Alert')); } catch (\Throwable $e) { Log::error('Operations email failed', ['error'=>$e->getMessage()]); }
        }
    }
}
