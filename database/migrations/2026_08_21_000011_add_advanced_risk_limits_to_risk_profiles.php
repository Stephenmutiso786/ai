<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
 public function up(): void { Schema::table('risk_profiles', function(Blueprint $t){ if(!Schema::hasColumn('risk_profiles','max_slippage_bps')) $t->unsignedInteger('max_slippage_bps')->default(25); if(!Schema::hasColumn('risk_profiles','max_spread_bps')) $t->unsignedInteger('max_spread_bps')->default(20); if(!Schema::hasColumn('risk_profiles','cooldown_seconds')) $t->unsignedInteger('cooldown_seconds')->default(0); }); }
 public function down(): void { Schema::table('risk_profiles', function(Blueprint $t){ foreach(['max_slippage_bps','max_spread_bps','cooldown_seconds'] as $c){ if(Schema::hasColumn('risk_profiles',$c)) $t->dropColumn($c); } }); }
};
