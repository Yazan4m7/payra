<?php
use Illuminate\Database\Migrations\Migration; use Illuminate\Database\Schema\Blueprint; use Illuminate\Support\Facades\Schema;
return new class extends Migration { public function up(): void {
 Schema::create('compliance_settings', function(Blueprint $t){$t->id();$t->string('version_label');$t->date('effective_date')->index();$t->json('settings');$t->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();$t->timestamps();$t->softDeletes();$t->unique(['effective_date','version_label']);});
 Schema::create('public_holidays', function(Blueprint $t){$t->id();$t->date('date')->unique();$t->string('name_ar');$t->string('name_en');$t->unsignedSmallInteger('year')->index();$t->timestamps();$t->softDeletes();});
 } public function down(): void {Schema::dropIfExists('public_holidays');Schema::dropIfExists('compliance_settings');} };
