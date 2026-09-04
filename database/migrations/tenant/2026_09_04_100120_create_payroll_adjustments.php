<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payroll_adjustments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained()->cascadeOnDelete();
            $table->enum('kind', ['earning', 'deduction']);
            $table->string('name');
            $table->decimal('amount', 16, 3);
            $table->unsignedTinyInteger('payment_month');
            $table->unsignedSmallInteger('payment_year');
            $table->unsignedTinyInteger('source_month')->nullable();
            $table->unsignedSmallInteger('source_year')->nullable();
            $table->boolean('taxable')->default(true);
            $table->boolean('ssc_applicable')->default(true);
            $table->boolean('reduces_taxable_income')->default(false);
            $table->boolean('reduces_ssc_base')->default(false);
            $table->enum('status', ['pending', 'approved', 'applied', 'void'])->default('pending');
            $table->text('reason');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->foreignId('applied_payroll_run_id')->nullable()->constrained('payroll_runs')->nullOnDelete();
            $table->foreignId('applied_payslip_id')->nullable()->constrained('payslips')->nullOnDelete();
            $table->timestamp('applied_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['employee_id', 'payment_year', 'payment_month', 'status'], 'payroll_adj_payment_idx');
            $table->index(['source_year', 'source_month']);
        });

        Schema::table('payslips', function (Blueprint $table) {
            $table->decimal('adjustment_earnings_total', 16, 3)->default(0)->after('earnings_total');
            $table->decimal('adjustment_deductions_total', 16, 3)->default(0)->after('deductions_total');
        });
    }

    public function down(): void
    {
        Schema::table('payslips', function (Blueprint $table) {
            $table->dropColumn(['adjustment_earnings_total', 'adjustment_deductions_total']);
        });
        Schema::dropIfExists('payroll_adjustments');
    }
};
