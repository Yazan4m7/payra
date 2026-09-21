<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('branches', function (Blueprint $table) {
            $table->id(); $table->string('code', 50)->unique(); $table->string('name');
            $table->string('city')->nullable(); $table->boolean('active')->default(true)->index();
            $table->timestamps(); $table->softDeletes();
        });
        Schema::create('departments', function (Blueprint $table) {
            $table->id(); $table->string('code', 50)->unique(); $table->string('name');
            $table->foreignId('branch_id')->nullable()->constrained()->nullOnDelete();
            $table->boolean('active')->default(true)->index(); $table->timestamps(); $table->softDeletes();
        });
        Schema::create('cost_centers', function (Blueprint $table) {
            $table->id(); $table->string('code', 50)->unique(); $table->string('name');
            $table->foreignId('department_id')->nullable()->constrained()->nullOnDelete();
            $table->boolean('active')->default(true)->index(); $table->timestamps(); $table->softDeletes();
        });
        Schema::table('employees', function (Blueprint $table) {
            $table->foreignId('branch_id')->nullable()->after('job_title')->constrained()->nullOnDelete();
            $table->foreignId('department_id')->nullable()->after('branch_id')->constrained()->nullOnDelete();
            $table->foreignId('cost_center_id')->nullable()->after('department_id')->constrained()->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->dropForeign(['branch_id']); $table->dropForeign(['department_id']); $table->dropForeign(['cost_center_id']);
            $table->dropColumn(['branch_id','department_id','cost_center_id']);
        });
        Schema::dropIfExists('cost_centers'); Schema::dropIfExists('departments'); Schema::dropIfExists('branches');
    }
};
