<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('subscription_plans', function (Blueprint $table) {
            $table->id();
            $table->string('name');           // Demo, Basic, Standard, Pro
            $table->string('slug')->unique();
            $table->unsignedInteger('price_usd_weekly')->nullable(); // null = "contact us" / not yet priced
            $table->unsignedSmallInteger('runs_per_week')->nullable(); // null = unlimited
            $table->unsignedSmallInteger('total_runs_lifetime')->nullable(); // used by Demo: 1 run, ever
            $table->boolean('automation_allowed')->default(false);
            $table->boolean('is_demo')->default(false);
            // The Custom plan is a template row shown as "Request a custom
            // package" -- it's never assigned directly; approving a request
            // clones its terms into a subscription with the admin-set
            // override_price_usd_weekly / override_runs_per_week below.
            $table->boolean('is_custom_template')->default(false);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('subscriptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('subscription_plan_id')->constrained();
            $table->enum('status', ['trialing', 'active', 'past_due', 'cancelled'])->default('trialing');

            // Set only for custom-package clients, once an admin approves
            // their request. When present, these override the plan's own
            // price/runs for this specific subscription.
            $table->unsignedInteger('override_price_usd_weekly')->nullable();
            $table->unsignedSmallInteger('override_runs_per_week')->nullable();
            $table->boolean('override_runs_unlimited')->default(false);

            // Usage-window bookkeeping for the runs-per-week limit.
            $table->unsignedSmallInteger('runs_used_this_period')->default(0);
            $table->timestamp('period_started_at')->nullable();

            $table->timestamp('current_period_start')->nullable();
            $table->timestamp('current_period_end')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('subscriptions');
        Schema::dropIfExists('subscription_plans');
    }
};
