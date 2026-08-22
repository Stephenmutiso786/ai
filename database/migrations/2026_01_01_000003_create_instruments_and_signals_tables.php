<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('instruments', function (Blueprint $table) {
            $table->id();
            $table->string('symbol')->unique(); // EURUSD, XAUUSD...
            $table->string('category');         // major, cross, metal, index, crypto
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('ai_signals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('instrument_id')->constrained();
            $table->enum('direction', ['buy', 'sell', 'wait']);
            $table->unsignedTinyInteger('confidence'); // 0-100
            $table->decimal('entry', 12, 5)->nullable();
            $table->decimal('stop_loss', 12, 5)->nullable();
            $table->decimal('take_profit', 12, 5)->nullable();
            $table->decimal('risk_reward', 5, 2)->nullable();
            $table->string('market_regime')->nullable();   // trending, ranging, volatile
            $table->text('reasoning')->nullable();          // plain-language explanation only
            $table->timestamp('generated_at');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_signals');
        Schema::dropIfExists('instruments');
    }
};
