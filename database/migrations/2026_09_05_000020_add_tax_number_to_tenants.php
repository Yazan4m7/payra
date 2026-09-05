<?php
use Illuminate\Database\Migrations\Migration; use Illuminate\Database\Schema\Blueprint; use Illuminate\Support\Facades\Schema;
return new class extends Migration { public function up():void{Schema::table('tenants',fn(Blueprint $t)=>$t->string('tax_number',100)->nullable()->after('ssc_establishment_number'));} public function down():void{Schema::table('tenants',fn(Blueprint $t)=>$t->dropColumn('tax_number'));} };
