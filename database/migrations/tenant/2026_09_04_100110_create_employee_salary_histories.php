<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('employee_salary_histories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained()->cascadeOnDelete();
            $table->decimal('amount', 16, 3);
            $table->date('effective_from');
            $table->string('reason')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->unique(['employee_id', 'effective_from']);
            $table->index(['employee_id', 'effective_from']);
        });

        foreach (DB::table('employees')->select(['id', 'salary', 'hire_date'])->orderBy('id')->get() as $employee) {
            DB::table('employee_salary_histories')->insert([
                'employee_id' => $employee->id,
                'amount' => $employee->salary,
                'effective_from' => $employee->hire_date,
                'reason' => 'Initial salary backfill',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('employee_salary_histories');
    }
};
