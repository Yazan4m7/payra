<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('employee_loans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained()->cascadeOnDelete();
            $table->enum('type', ['loan', 'advance'])->default('loan');
            $table->string('name');
            $table->decimal('principal', 16, 3);
            $table->decimal('installment_amount', 16, 3);
            $table->date('starts_on');
            $table->date('ends_on')->nullable();
            $table->enum('status', ['active', 'paused', 'cancelled'])->default('active');
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
            $table->index(['employee_id', 'status', 'starts_on']);
        });

        Schema::create('loan_repayments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_loan_id')->constrained()->cascadeOnDelete();
            $table->foreignId('payroll_run_id')->constrained()->cascadeOnDelete();
            $table->foreignId('payslip_id')->constrained()->cascadeOnDelete();
            $table->decimal('amount', 16, 3);
            $table->timestamps();
            $table->unique(['employee_loan_id', 'payroll_run_id']);
        });

        Schema::table('payslips', function (Blueprint $table) {
            $table->decimal('loan_deductions_total', 16, 3)->default(0)->after('deductions_total');
        });
    }

    public function down(): void
    {
        Schema::table('payslips', function (Blueprint $table) {
            $table->dropColumn('loan_deductions_total');
        });

        Schema::dropIfExists('loan_repayments');
        Schema::dropIfExists('employee_loans');
    }
};
