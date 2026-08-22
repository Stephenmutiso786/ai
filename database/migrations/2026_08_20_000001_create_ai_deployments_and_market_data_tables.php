<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
 public function up(): void {
  Schema::create('ai_models', function(Blueprint $t){
   $t->id();
   $t->string('name');
   $t->string('version');
   $t->string('framework')->default('python');
   $t->enum('status',['draft','training','trained','validating','paper','shadow','approved','live','retired','failed'])->default('draft');
   $t->string('artifact_uri')->nullable();
   $t->json('metrics')->nullable();
   $t->json('parameters')->nullable();
   $t->text('notes')->nullable();
   $t->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
   $t->timestamps();
   $t->unique(['name','version']);
  });
  Schema::create('ai_model_deployments', function(Blueprint $t){$t->id();$t->foreignId('ai_model_id')->constrained()->cascadeOnDelete();$t->enum('environment',['paper','shadow','live']);$t->enum('status',['active','paused','rolled_back'])->default('active');$t->json('config')->nullable();$t->timestamp('deployed_at')->nullable();$t->timestamp('rolled_back_at')->nullable();$t->foreignId('deployed_by')->nullable()->constrained('users')->nullOnDelete();$t->timestamps();$t->index(['environment','status']);});
  // market_data_ingestions is created in
  // 2026_08_21_000100_create_market_data_ingestions_table.php -- it used
  // to also be created here, which duplicated the table and broke
  // `migrate` outright. Removed from this file rather than the other one
  // since the 08_21 version's schema is the more complete of the two and
  // neither was referenced by any model/controller yet.
 }
 public function down(): void {Schema::dropIfExists('ai_model_deployments'); Schema::dropIfExists('ai_models');}
};
