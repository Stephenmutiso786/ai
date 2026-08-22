<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('risk_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('label')->default('Conservative');
            $table->decimal('max_risk_per_trade_pct', 4, 2)->default(0.5);
            $table->decimal('max_daily_loss_pct', 4, 2)->default(2.0);
            $table->decimal('max_weekly_loss_pct', 4, 2)->default(5.0);
            $table->unsignedTinyInteger('max_open_positions')->default(3);
            $table->decimal('max_exposure_pct', 5, 2)->default(10.0);
            $table->boolean('trading_halted')->default(false); // flips true when a limit is breached
            $table->timestamps();
        });

        Schema::create('trades', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained();
            $table->foreignId('broker_account_id')->nullable()->constrained();
            $table->foreignId('ai_signal_id')->nullable()->constrained();
            $table->foreignId('instrument_id')->constrained();
            $table->enum('side', ['buy', 'sell']);
            $table->enum('mode', ['paper', 'live'])->default('paper');
            $table->decimal('lot_size', 8, 2);
            $table->decimal('entry_price', 12, 5);
            $table->decimal('stop_loss', 12, 5)->nullable();
            $table->decimal('take_profit', 12, 5)->nullable();
            $table->decimal('close_price', 12, 5)->nullable();
            $table->decimal('profit_loss', 12, 2)->nullable();
            $table->enum('status', ['open', 'closed', 'rejected'])->default('open');
            $table->string('rejection_reason')->nullable(); // set by the risk engine, never the AI
            $table->timestamp('opened_at')->nullable();
            $table->timestamp('closed_at')->nullable();
            $table->timestamps();
        });

        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained();
            $table->string('action');       // signal_generated, risk_check_passed, risk_check_failed, trade_opened, kill_switch...
            $table->json('context')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('audit_logs');
        Schema::dropIfExists('trades');
        Schema::dropIfExists('risk_profiles');
    }
};
