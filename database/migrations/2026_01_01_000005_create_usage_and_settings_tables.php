<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('custom_plan_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->text('message'); // what the client needs, in their own words
            $table->unsignedSmallInteger('requested_runs_per_week')->nullable();
            $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending');
            $table->unsignedInteger('approved_price_usd_weekly')->nullable();
            $table->unsignedSmallInteger('approved_runs_per_week')->nullable();
            $table->boolean('approved_runs_unlimited')->default(false);
            $table->text('admin_notes')->nullable();
            $table->foreignId('reviewed_by')->nullable()->constrained('users');
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamps();
        });

        // One row per AI "run" a client actually consumes -- the ledger
        // both the weekly-limit check and the demo one-time check read from.
        Schema::create('usage_runs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('subscription_id')->nullable()->constrained();
            $table->json('context')->nullable(); // which instruments/timeframes were analysed
            $table->string('ip_address', 45)->nullable();
            $table->timestamp('created_at')->useCurrent();
        });

        // Demo-plan usage keyed to a device fingerprint, not an account --
        // this is what actually stops "one demo run per device" from being
        // bypassed by creating a second account on the same device.
        // NOTE: this is a composite browser/IP fingerprint, not a MAC
        // address -- websites cannot read a device's MAC address; no
        // browser exposes that to any site, for any user, ever.
        Schema::create('demo_device_usage', function (Blueprint $table) {
            $table->id();
            $table->string('device_hash')->unique();
            $table->foreignId('user_id')->nullable()->constrained();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->timestamp('used_at');
            $table->timestamps();
        });

        // Admin-managed API keys / integration credentials. Values are
        // encrypted at rest with Laravel's APP_KEY (Crypt facade), never
        // stored in plaintext or committed to code.
        Schema::create('settings', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->text('value_encrypted')->nullable();
            $table->foreignId('updated_by')->nullable()->constrained('users');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('settings');
        Schema::dropIfExists('demo_device_usage');
        Schema::dropIfExists('usage_runs');
        Schema::dropIfExists('custom_plan_requests');
    }
};
