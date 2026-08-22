<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('system_incidents', function (Blueprint $table) {
            $table->id();
            $table->string('fingerprint')->unique();
            $table->string('component');
            $table->string('severity');
            $table->string('status')->default('open');
            $table->string('title');
            $table->text('details')->nullable();
            $table->json('context')->nullable();
            $table->timestamp('first_seen_at');
            $table->timestamp('last_seen_at');
            $table->timestamp('acknowledged_at')->nullable();
            $table->timestamp('resolved_at')->nullable();
            $table->unsignedBigInteger('acknowledged_by')->nullable();
            $table->timestamps();
        });
        Schema::create('system_health_checks', function (Blueprint $table) {
            $table->id();
            $table->string('component');
            $table->string('status');
            $table->unsignedInteger('latency_ms')->nullable();
            $table->text('message')->nullable();
            $table->json('meta')->nullable();
            $table->timestamp('checked_at');
            $table->timestamps();
            $table->index(['component','checked_at']);
        });
        Schema::create('backup_runs', function (Blueprint $table) {
            $table->id();
            $table->string('type')->default('postgres');
            $table->string('status');
            $table->string('location')->nullable();
            $table->unsignedBigInteger('size_bytes')->nullable();
            $table->char('sha256', 64)->nullable();
            $table->text('message')->nullable();
            $table->timestamp('started_at');
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
        });
    }
    public function down(): void { Schema::dropIfExists('backup_runs'); Schema::dropIfExists('system_health_checks'); Schema::dropIfExists('system_incidents'); }
};
