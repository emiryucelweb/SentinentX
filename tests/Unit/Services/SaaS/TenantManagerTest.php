<?php

namespace Tests\Unit\Services\SaaS;

use App\Services\SaaS\TenantManager;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class TenantManagerTest extends TestCase
{
    use RefreshDatabase;

    private TenantManager $tenantManager;

    protected function setUp(): void
    {
        parent::setUp();
        $this->tenantManager = app(TenantManager::class);
    }

    public function test_set_tenant_changes_current_tenant()
    {
        $tenantId = 'test-tenant-123';

        $this->tenantManager->setTenant($tenantId);

        $this->assertEquals($tenantId, $this->tenantManager->getCurrentTenant());
    }

    public function test_get_current_tenant_returns_default_initially()
    {
        $currentTenant = $this->tenantManager->getCurrentTenant();

        $this->assertEquals('default', $currentTenant);
    }

    public function test_set_and_get_tenant_works()
    {
        $testTenantId = 'test-tenant-456';

        $this->tenantManager->setTenant($testTenantId);
        $currentTenant = $this->tenantManager->getCurrentTenant();

        $this->assertEquals($testTenantId, $currentTenant);
    }

    public function test_tenant_context_isolation()
    {
        $tenant1 = 'tenant-one';
        $tenant2 = 'tenant-two';

        // Test tenant switching
        $this->tenantManager->setTenant($tenant1);
        $this->assertEquals($tenant1, $this->tenantManager->getCurrentTenant());

        $this->tenantManager->setTenant($tenant2);
        $this->assertEquals($tenant2, $this->tenantManager->getCurrentTenant());

        // Test that context persists
        $this->assertEquals($tenant2, $this->tenantManager->getCurrentTenant());
    }

    protected function tearDown(): void
    {
        // Clean up any tenant schemas created during tests
        Cache::flush();
        parent::tearDown();
    }
}
