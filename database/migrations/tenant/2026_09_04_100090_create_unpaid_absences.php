<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('unpaid_absences', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained()->cascadeOnDelete();
            $table->enum('type', ['unpaid_leave', 'absence'])->default('unpaid_leave');
            $table->date('start_date');
            $table->date('end_date');
            $table->decimal('days', 8, 2)->default(0);
            $table->enum('status', ['pending', 'approved', 'rejected', 'cancelled'])->default('pending');
            $table->foreignId('approver_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->text('reason')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->index(['employee_id', 'status', 'start_date', 'end_date']);
        });

        Schema::table('payslips', function (Blueprint $table) {
            $table->decimal('unpaid_absence_deduction', 16, 3)->default(0)->after('loan_deductions_total');
        });
    }

    public function down(): void
    {
        Schema::table('payslips', function (Blueprint $table) {
            $table->dropColumn('unpaid_absence_deduction');
        });

        Schema::dropIfExists('unpaid_absences');
    }
};
