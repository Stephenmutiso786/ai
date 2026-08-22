<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('ai_datasets', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('provider')->nullable();
            $table->string('instrument_symbol')->nullable();
            $table->string('timeframe')->nullable();
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('ends_at')->nullable();
            $table->unsignedBigInteger('row_count')->default(0);
            $table->string('storage_uri')->nullable();
            $table->enum('status', ['draft','validating','ready','failed'])->default('draft');
            $table->json('metadata')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('ai_models', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('version');
            $table->string('framework')->default('python');
            $table->enum('status', ['draft','training','trained','validating','paper','shadow','approved','live','retired','failed'])->default('draft');
            $table->string('artifact_uri')->nullable();
            $table->json('metrics')->nullable();
            $table->json('parameters')->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->unique(['name','version']);
        });

        Schema::create('ai_training_runs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ai_model_id')->constrained()->cascadeOnDelete();
            $table->foreignId('ai_dataset_id')->nullable()->constrained()->nullOnDelete();
            $table->enum('status', ['queued','running','completed','failed','cancelled'])->default('queued');
            $table->string('job_reference')->nullable();
            $table->json('config')->nullable();
            $table->json('metrics')->nullable();
            $table->text('error_message')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('finished_at')->nullable();
            $table->foreignId('requested_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('ai_backtests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ai_model_id')->constrained()->cascadeOnDelete();
            $table->string('instrument_symbol');
            $table->string('timeframe');
            $table->timestamp('starts_at');
            $table->timestamp('ends_at');
            $table->enum('status', ['queued','running','completed','failed'])->default('queued');
            $table->json('config')->nullable();
            $table->json('results')->nullable();
            $table->text('error_message')->nullable();
            $table->foreignId('requested_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_backtests');
        Schema::dropIfExists('ai_training_runs');
        Schema::dropIfExists('ai_models');
        Schema::dropIfExists('ai_datasets');
    }
};
