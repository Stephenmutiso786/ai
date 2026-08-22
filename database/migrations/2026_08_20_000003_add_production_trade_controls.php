<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
 public function up(): void {
  Schema::table('broker_accounts', function(Blueprint $t){ $t->timestamp('last_reconciliation_at')->nullable()->index(); $t->timestamp('execution_paused_at')->nullable(); });
  Schema::table('trades', function(Blueprint $t){ $t->string('broker_position_id')->nullable()->index(); });
 }
 public function down(): void { Schema::table('trades',fn(Blueprint $t)=>$t->dropColumn(['broker_position_id'])); Schema::table('broker_accounts',fn(Blueprint $t)=>$t->dropColumn(['last_reconciliation_at','execution_paused_at'])); }
};
