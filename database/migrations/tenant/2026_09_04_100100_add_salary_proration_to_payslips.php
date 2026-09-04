<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payslips', function (Blueprint $table) {
            $table->decimal('contract_salary', 16, 3)->default(0)->before('gross_salary');
            $table->decimal('proration_adjustment', 16, 3)->default(0)->after('gross_salary');
        });
    }

    public function down(): void
    {
        Schema::table('payslips', function (Blueprint $table) {
            $table->dropColumn(['contract_salary', 'proration_adjustment']);
        });
    }
};
