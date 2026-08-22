<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
 public function up(): void {
  Schema::create('payment_transactions', function (Blueprint $table) {
   $table->id();
   $table->foreignId('user_id')->constrained()->cascadeOnDelete();
   $table->foreignId('subscription_plan_id')->nullable()->constrained()->nullOnDelete();
   $table->string('provider');
   $table->string('provider_reference')->nullable()->index();
   $table->string('merchant_reference')->unique();
   $table->string('currency', 12);
   $table->unsignedBigInteger('amount_minor');
   $table->enum('status', ['pending','processing','paid','failed','cancelled','refunded'])->default('pending');
   $table->json('metadata')->nullable();
   $table->timestamp('paid_at')->nullable();
   $table->timestamps();
   $table->unique(['provider','provider_reference']);
  });
  Schema::table('subscriptions', function (Blueprint $table) {
   $table->string('payment_provider')->nullable()->after('status');
   $table->boolean('cancel_at_period_end')->default(false)->after('payment_provider');
  });
 }
 public function down(): void { Schema::table('subscriptions', fn(Blueprint $t) => $t->dropColumn(['payment_provider','cancel_at_period_end'])); Schema::dropIfExists('payment_transactions'); }
};
