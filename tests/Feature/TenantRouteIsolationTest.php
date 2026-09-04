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
        $this->assertStringContainsString("Route::middleware('auth')", $routes);

        $initialize = strpos($routes, "Route::middleware(['web', InitializeTenancyByDomain::class");
        $auth = strpos($routes, "Route::middleware('auth')");
        $this->assertNotFalse($initialize);
        $this->assertNotFalse($auth);
        $this->assertLessThan($auth, $initialize, 'Tenancy must be initialized before tenant authentication/data access.');
    }

    public function test_central_operator_uses_a_separate_auth_guard_and_connection(): void
    {
        $auth = file_get_contents(__DIR__.'/../../config/auth.php');
        $model = file_get_contents(__DIR__.'/../../app/Models/CentralUser.php');
        $this->assertStringContainsString("'central'", $auth);
        $this->assertStringContainsString("protected \$connection = 'central'", $model);
    }
}
