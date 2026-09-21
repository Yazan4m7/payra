<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE payroll_runs MODIFY status ENUM('draft','processing','calculated','approved','completed','void') NOT NULL DEFAULT 'draft'");

        Schema::table('payroll_runs', function (Blueprint $table) {
            $table->foreignId('approved_by')->nullable()->after('created_by')->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable()->after('completed_at');
            $table->timestamp('locked_at')->nullable()->after('approved_at');
            $table->char('calculation_hash', 64)->nullable()->after('locked_at');
        });

        DB::table('payroll_runs')->where('status', 'completed')->orderBy('id')->eachById(function ($run) {
            DB::table('payroll_runs')->where('id', $run->id)->update([
                'status' => 'approved',
                'approved_at' => $run->completed_at,
                'locked_at' => $run->completed_at,
            ]);
        });
    }

    public function down(): void
    {
        DB::table('payroll_runs')->where('status', 'approved')->update(['status' => 'completed']);
        Schema::table('payroll_runs', function (Blueprint $table) {
            $table->dropForeign(['approved_by']);
            $table->dropColumn(['approved_by', 'approved_at', 'locked_at', 'calculation_hash']);
        });
        DB::statement("ALTER TABLE payroll_runs MODIFY status ENUM('draft','processing','completed','void') NOT NULL DEFAULT 'draft'");
    }
};
