<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('broker_accounts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('broker');            // e.g. HFM
            $table->string('platform');          // MT5
            $table->string('server');
            $table->string('account_number');
            // Never store raw passwords. This holds an encrypted token/blob
            // produced by the broker's supported API/integration mechanism,
            // encrypted with BROKER_CREDENTIAL_CIPHER_KEY (see .env.example).
            $table->text('credential_payload_encrypted')->nullable();
            $table->enum('trading_mode', ['signals_only', 'semi_automatic', 'fully_automatic'])
                ->default('signals_only');
            $table->enum('connection_status', ['pending', 'connected', 'failed', 'disconnected'])
                ->default('pending');
            $table->timestamp('connected_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('broker_accounts');
    }
};
