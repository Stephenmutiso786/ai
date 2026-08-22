<?php
namespace App\Console\Commands;
use App\Models\Subscription; use Illuminate\Console\Command;
class ExpireSubscriptions extends Command { protected $signature='subscriptions:expire'; protected $description='Expire paid subscriptions whose current period has ended'; public function handle(): int { $count=Subscription::where('status','active')->whereNotNull('current_period_end')->where('current_period_end','<',now())->where('cancel_at_period_end',true)->update(['status'=>'cancelled']); $this->info("Cancelled {$count} expired subscriptions."); return self::SUCCESS; } }
