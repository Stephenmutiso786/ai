<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
 public function up(): void { Schema::create('ai_model_validations', function (Blueprint $table) {
  $table->id(); $table->foreignId('ai_model_id')->nullable()->constrained()->nullOnDelete();
  $table->string('validation_type'); $table->string('status'); $table->json('metrics')->nullable();
  $table->json('rules')->nullable(); $table->text('notes')->nullable(); $table->timestamp('validated_at')->nullable(); $table->timestamps();
 }); }
 public function down(): void { Schema::dropIfExists('ai_model_validations'); }
};
