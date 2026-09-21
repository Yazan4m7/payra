<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('performance_cycles', function (Blueprint $table) {
            $table->id(); $table->string('name'); $table->date('starts_on'); $table->date('ends_on');
            $table->enum('status',['draft','open','closed'])->default('draft')->index();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
        Schema::create('performance_reviews', function (Blueprint $table) {
            $table->id(); $table->foreignId('performance_cycle_id')->constrained()->cascadeOnDelete();
            $table->foreignId('employee_id')->constrained()->cascadeOnDelete();
            $table->foreignId('reviewer_id')->nullable()->constrained('users')->nullOnDelete();
            $table->enum('status',['draft','submitted','acknowledged'])->default('draft')->index();
            $table->decimal('overall_rating',3,2)->nullable(); $table->decimal('goals_rating',3,2)->nullable(); $table->decimal('competency_rating',3,2)->nullable();
            $table->text('strengths')->nullable(); $table->text('improvements')->nullable(); $table->text('goals')->nullable(); $table->text('employee_comment')->nullable();
            $table->timestamp('submitted_at')->nullable(); $table->timestamp('acknowledged_at')->nullable(); $table->timestamps();
            $table->unique(['performance_cycle_id','employee_id'],'performance_cycle_employee_uq');
        });
    }
    public function down(): void { Schema::dropIfExists('performance_reviews'); Schema::dropIfExists('performance_cycles'); }
};
