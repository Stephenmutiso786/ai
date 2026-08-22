<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
 public function up(): void {
  Schema::create('idempotency_keys', function(Blueprint $t){
   $t->id(); $t->string('scope'); $t->string('idempotency_key',128); $t->string('request_hash',64); $t->string('status',32);
   $t->longText('response_json')->nullable(); $t->timestamps(); $t->unique(['scope','idempotency_key']);
  });
  Schema::create('execution_events', function(Blueprint $t){
   $t->id(); $t->foreignId('trade_id')->nullable()->constrained()->nullOnDelete(); $t->foreignId('broker_account_id')->nullable()->constrained()->nullOnDelete();
   $t->string('event_type'); $t->string('state'); $t->json('payload')->nullable(); $t->timestamp('occurred_at')->useCurrent(); $t->timestamps();
   $t->index(['trade_id','occurred_at']); $t->index(['broker_account_id','occurred_at']);
  });
 }
 public function down(): void { Schema::dropIfExists('execution_events'); Schema::dropIfExists('idempotency_keys'); }
};
