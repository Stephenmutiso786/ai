<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
 public function up(): void { Schema::create('market_data_ingestions', function (Blueprint $table) {
  $table->id(); $table->string('provider'); $table->string('symbol'); $table->string('timeframe');
  $table->timestamp('from_time')->nullable(); $table->timestamp('to_time')->nullable();
  $table->string('status')->default('pending'); $table->unsignedInteger('records')->default(0);
  $table->json('metadata')->nullable(); $table->text('error')->nullable(); $table->timestamps();
  $table->index(['provider','symbol','timeframe','status']);
 }); }
 public function down(): void { Schema::dropIfExists('market_data_ingestions'); }
};
