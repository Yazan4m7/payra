<?php
use Illuminate\Database\Migrations\Migration; use Illuminate\Database\Schema\Blueprint; use Illuminate\Support\Facades\Schema;
return new class extends Migration { public function up():void{Schema::create('gl_account_mappings',function(Blueprint $t){$t->id();$t->string('key')->unique();$t->string('account_code');$t->string('account_name');$t->timestamps();});} public function down():void{Schema::dropIfExists('gl_account_mappings');}};
