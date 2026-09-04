<?php

namespace Tests\Feature;

use PHPUnit\Framework\TestCase;

class TenantRouteIsolationTest extends TestCase
{
    public function test_tenant_routes_initialize_and_scope_tenancy_before_application_routes(): void
    {
        $routes = file_get_contents(__DIR__.'/../../routes/tenant.php');

        $this->assertStringContainsString('InitializeTenancyByDomain::class', $routes);
        $this->assertStringContainsString('PreventAccessFromCentralDomains::class', $routes);
        $this->assertStringContainsString('ScopeSessions::class', $routes);
        $this->assertMatchesRegularExpression("/Route::middleware\\(\\s*['\"]auth['\"]\\s*\\)/", $routes);

        preg_match('/Route::middleware\\(\\s*\\[.*?InitializeTenancyByDomain::class/s', $routes, $tenancyMatch, PREG_OFFSET_CAPTURE);
        preg_match('/Route::middleware\\(\\s*[\'\"]auth[\'\"]\\s*\\)/', $routes, $authMatch, PREG_OFFSET_CAPTURE);

        $this->assertNotEmpty($tenancyMatch, 'Tenant middleware group must initialize tenancy.');
        $this->assertNotEmpty($authMatch, 'Tenant auth middleware group must exist.');
        $this->assertLessThan(
            $authMatch[0][1],
            $tenancyMatch[0][1],
            'Tenancy must be initialized before tenant authentication/data access.'
        );
    }

    public function test_central_operator_uses_a_separate_auth_guard_and_connection(): void
    {
        $auth = file_get_contents(__DIR__.'/../../config/auth.php');
        $model = file_get_contents(__DIR__.'/../../app/Models/CentralUser.php');

        $this->assertStringContainsString("'central'", $auth);
        $this->assertStringContainsString("protected \$connection = 'central'", $model);
    }
}
