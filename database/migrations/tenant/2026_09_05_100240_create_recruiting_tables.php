<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('job_openings', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->foreignId('department_id')->nullable()->constrained()->nullOnDelete();
            $table->string('location')->nullable();
            $table->enum('employment_type', ['full_time','part_time','contract','internship'])->default('full_time');
            $table->unsignedSmallInteger('openings_count')->default(1);
            $table->enum('status', ['draft','open','closed'])->default('draft')->index();
            $table->date('opened_on')->nullable();
            $table->date('closed_on')->nullable();
            $table->text('description')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
        });
        Schema::create('candidates', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->nullable()->index();
            $table->string('phone', 50)->nullable();
            $table->string('national_id', 50)->nullable()->index();
            $table->string('source')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
        Schema::create('candidate_applications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('candidate_id')->constrained()->cascadeOnDelete();
            $table->foreignId('job_opening_id')->constrained()->cascadeOnDelete();
            $table->enum('stage', ['applied','screening','interview','offer','hired','rejected'])->default('applied')->index();
            $table->timestamp('applied_at');
            $table->timestamp('stage_changed_at')->nullable();
            $table->foreignId('stage_changed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('hired_employee_id')->nullable()->constrained('employees')->nullOnDelete();
            $table->text('decision_note')->nullable();
            $table->timestamps();
            $table->unique(['candidate_id','job_opening_id'], 'candidate_job_application_uq');
        });
    }
    public function down(): void
    {
        Schema::dropIfExists('candidate_applications');
        Schema::dropIfExists('candidates');
        Schema::dropIfExists('job_openings');
    }
};
