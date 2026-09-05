<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('employee_deductions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained()->cascadeOnDelete();
            $table->string('category', 50)->default('other');
            $table->string('name');
            $table->decimal('amount', 16, 3);
            $table->boolean('recurring')->default(true);
            $table->date('starts_on');
            $table->date('ends_on')->nullable();
            $table->date('one_time_date')->nullable();
            $table->boolean('reduces_taxable_income')->default(false);
            $table->boolean('reduces_ssc_base')->default(false);
            $table->boolean('active')->default(true);
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['employee_id', 'active']);
            $table->index(['recurring', 'starts_on', 'ends_on']);
            $table->index('one_time_date');
        });

        Schema::table('payslips', function (Blueprint $table) {
            $table->decimal('deductions_total', 16, 3)->default(0)->after('earnings_total');
        });
    }

    public function down(): void
    {
        Schema::table('payslips', function (Blueprint $table) {
            $table->dropColumn('deductions_total');
        });

        Schema::dropIfExists('employee_deductions');
    }
};
