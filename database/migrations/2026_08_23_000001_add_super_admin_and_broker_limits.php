<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('is_super_admin')->default(false)->after('role');
        });

        Schema::table('subscription_plans', function (Blueprint $table) {
            $table->unsignedSmallInteger('broker_connections_limit')->nullable()->after('automation_allowed');
        });

        Schema::table('broker_accounts', function (Blueprint $table) {
            $table->timestamp('verified_at')->nullable()->after('connected_at');
            $table->decimal('balance', 18, 2)->nullable()->after('verified_at');
            $table->decimal('equity', 18, 2)->nullable()->after('balance');
            $table->decimal('margin_available', 18, 2)->nullable()->after('equity');
            $table->string('currency', 12)->nullable()->after('margin_available');
        });
    }

    public function down(): void
    {
        Schema::table('broker_accounts', function (Blueprint $table) {
            $table->dropColumn(['balance', 'equity', 'margin_available', 'currency']);
            $table->dropColumn('verified_at');
        });

        Schema::table('subscription_plans', function (Blueprint $table) {
            $table->dropColumn('broker_connections_limit');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('is_super_admin');
        });
    }
};
