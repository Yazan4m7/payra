<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('employee_earnings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained()->cascadeOnDelete();
            $table->enum('category', ['allowance', 'bonus', 'commission']);
            $table->string('name');
            $table->decimal('amount', 16, 3);
            $table->boolean('recurring')->default(true);
            $table->date('starts_on');
            $table->date('ends_on')->nullable();
            $table->date('one_time_date')->nullable();
            $table->boolean('taxable')->default(true);
            $table->boolean('ssc_applicable')->default(true);
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
            $table->decimal('earnings_total', 16, 3)->default(0)->after('overtime_pay');
        });
    }

    public function down(): void
    {
        Schema::table('payslips', function (Blueprint $table) {
            $table->dropColumn('earnings_total');
        });

        Schema::dropIfExists('employee_earnings');
    }
};
