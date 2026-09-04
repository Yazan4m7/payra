<?php
use Illuminate\Database\Migrations\Migration; use Illuminate\Database\Schema\Blueprint; use Illuminate\Support\Facades\Schema;
return new class extends Migration { public function up(): void { Schema::connection('central')->create('central_users', function(Blueprint $t){$t->id();$t->string('name');$t->string('email')->unique();$t->string('password');$t->boolean('is_super_admin')->default(true);$t->rememberToken();$t->timestamps();$t->softDeletes();}); } public function down(): void { Schema::connection('central')->dropIfExists('central_users'); } };
