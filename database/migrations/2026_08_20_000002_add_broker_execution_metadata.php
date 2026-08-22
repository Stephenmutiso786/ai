<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
 public function up(): void {
  Schema::table('broker_accounts', function(Blueprint $t){
   $t->json('capabilities')->nullable()->after('connection_status');
   $t->timestamp('last_synced_at')->nullable()->after('connected_at');
   $t->string('external_account_id')->nullable()->after('account_number');
  });
  Schema::table('trades', function(Blueprint $t){ $t->string('broker_order_id')->nullable()->index()->after('status'); });
 }
 public function down(): void { Schema::table('trades',fn(Blueprint $t)=>$t->dropColumn('broker_order_id')); Schema::table('broker_accounts',fn(Blueprint $t)=>$t->dropColumn(['capabilities','last_synced_at','external_account_id'])); }
};
