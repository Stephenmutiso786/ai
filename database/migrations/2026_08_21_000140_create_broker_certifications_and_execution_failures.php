<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('broker_certifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('broker_account_id')->constrained()->cascadeOnDelete();
            $table->string('status')->default('pending'); // pending, running, passed, failed
            $table->json('checks')->nullable();
            $table->json('details')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->foreignId('certified_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->index(['broker_account_id','status']);
        });

        Schema::create('execution_failures', function (Blueprint $table) {
            $table->id();
            $table->foreignId('trade_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('broker_account_id')->constrained()->cascadeOnDelete();
            $table->string('idempotency_key')->nullable()->index();
            $table->string('stage');
            $table->text('error');
            $table->json('context')->nullable();
            $table->unsignedInteger('attempts')->default(0);
            $table->string('status')->default('open'); // open, retrying, resolved, abandoned
            $table->timestamp('next_retry_at')->nullable();
            $table->timestamp('resolved_at')->nullable();
            $table->timestamps();
            $table->index(['broker_account_id','status','next_retry_at']);
        });
    }
    public function down(): void { Schema::dropIfExists('execution_failures'); Schema::dropIfExists('broker_certifications'); }
};
