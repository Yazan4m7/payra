<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up(): void {
        Schema::connection('central')->create('tenants', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->string('name');
            $table->string('sector')->nullable();
            $table->string('ssc_establishment_number')->nullable();
            $table->string('plan')->default('standard');
            $table->string('subscription_status')->default('trial');
            $table->date('subscription_ends_at')->nullable();
            $table->json('data')->nullable();
            $table->timestamps();
        });
        Schema::connection('central')->create('domains', function (Blueprint $table) {
            $table->id(); $table->string('domain')->unique(); $table->string('tenant_id'); $table->timestamps();
            $table->foreign('tenant_id')->references('id')->on('tenants')->cascadeOnUpdate()->cascadeOnDelete();
        });
    }
    public function down(): void { Schema::connection('central')->dropIfExists('domains'); Schema::connection('central')->dropIfExists('tenants'); }
};
