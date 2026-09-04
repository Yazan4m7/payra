<?php
namespace Tests\Feature;
use PHPUnit\Framework\TestCase;
class TenantArchitectureTest extends TestCase
{
    public function test_hr_tables_are_tenant_migrations_not_central_migrations(): void
    {
        $tenant=glob(__DIR__.'/../../database/migrations/tenant/*.php');
        $content=implode("\n",array_map('file_get_contents',$tenant));
        foreach(['employees','payroll_runs','payslips','leave_balances','leave_requests','overtime_entries','compliance_settings','public_holidays','termination_records'] as $table) $this->assertStringContainsString("'{$table}'",$content);
        $central=implode("\n",array_map('file_get_contents',glob(__DIR__.'/../../database/migrations/*.php')));
        $this->assertStringNotContainsString("Schema::connection('central')->create('employees'",$central);
    }
    public function test_no_tenant_id_column_is_used_as_a_substitute_for_database_isolation(): void
    {
        $content=implode("\n",array_map('file_get_contents',glob(__DIR__.'/../../database/migrations/tenant/*.php')));
        $this->assertStringNotContainsString("tenant_id",$content);
    }
}
