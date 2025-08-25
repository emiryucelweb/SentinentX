<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Models\Tenant;
use PHPUnit\Framework\TestCase;

class TenantTest extends TestCase
{
    #[Test]
    public function test_tenant_has_correct_table_name(): void
    {
        $tenant = new Tenant;

        $this->assertSame('tenants', $tenant->getTable());
    }

    #[Test]
    public function test_tenant_fillable_attributes(): void
    {
        $tenant = new Tenant;

        $expectedFillable = [
            'name',
            'domain',
            'database',
            'settings',
            'active',
            'meta',
        ];

        $this->assertSame($expectedFillable, $tenant->getFillable());
    }

    #[Test]
    public function test_tenant_casts(): void
    {
        $tenant = new Tenant;

        $this->assertSame('array', $tenant->getCasts()['settings']);
        $this->assertSame('array', $tenant->getCasts()['meta']);
    }

    #[Test]
    public function test_tenant_saas_multi_tenancy_fields(): void
    {
        $tenant = new Tenant;

        // Multi-tenancy essential fields
        $this->assertTrue(in_array('name', $tenant->getFillable()));
        $this->assertTrue(in_array('domain', $tenant->getFillable()));
        $this->assertTrue(in_array('database', $tenant->getFillable()));
    }

    #[Test]
    public function test_tenant_configuration_fields(): void
    {
        $tenant = new Tenant;

        // Configuration fields
        $this->assertTrue(in_array('settings', $tenant->getFillable()));
        $this->assertTrue(in_array('meta', $tenant->getFillable()));
    }

    #[Test]
    public function test_tenant_has_scope_methods(): void
    {
        $tenant = new Tenant;

        // Verify scope methods exist
        $this->assertTrue(method_exists($tenant, 'scopeByDomain'));
        $this->assertTrue(method_exists($tenant, 'scopeActive'));
    }

    #[Test]
    public function test_tenant_has_relationship_methods(): void
    {
        $tenant = new Tenant;

        // Verify relationship methods exist
        $this->assertTrue(method_exists($tenant, 'users'));
        $this->assertTrue(method_exists($tenant, 'subscriptions'));
    }

    #[Test]
    public function test_tenant_settings_array_cast(): void
    {
        $tenant = new Tenant;

        // Settings should be array for configuration
        $this->assertSame('array', $tenant->getCasts()['settings']);
    }

    #[Test]
    public function test_tenant_meta_array_cast(): void
    {
        $tenant = new Tenant;

        // Meta should be array for extensibility
        $this->assertSame('array', $tenant->getCasts()['meta']);
    }

    #[Test]
    public function test_tenant_saas_ready(): void
    {
        $tenant = new Tenant;

        // SaaS multi-tenancy essential fields
        $fillable = $tenant->getFillable();

        // Tenant identification
        $this->assertTrue(in_array('name', $fillable));
        $this->assertTrue(in_array('domain', $fillable));

        // Database isolation
        $this->assertTrue(in_array('database', $fillable));

        // Configuration
        $this->assertTrue(in_array('settings', $fillable));
        $this->assertTrue(in_array('meta', $fillable));
    }

    #[Test]
    public function test_tenant_billing_ready(): void
    {
        $tenant = new Tenant;

        // Billing essential relationships
        $this->assertTrue(method_exists($tenant, 'subscriptions'));
        $this->assertTrue(method_exists($tenant, 'users'));
    }

    #[Test]
    public function test_tenant_model_structure(): void
    {
        $tenant = new Tenant;

        // Verify model structure
        $reflection = new \ReflectionClass($tenant);

        // Tenant model should be extensible for SaaS needs
        $this->assertTrue($reflection->isSubclassOf(\Illuminate\Database\Eloquent\Model::class));

        // Verify HasFactory trait
        $this->assertTrue(in_array('Illuminate\Database\Eloquent\Factories\HasFactory', class_uses($tenant)));
    }

    #[Test]
    public function test_tenant_domain_scoping_method(): void
    {
        $tenant = new Tenant;

        // Domain-based tenant isolation method exists
        $this->assertTrue(method_exists($tenant, 'scopeByDomain'));
    }

    #[Test]
    public function test_tenant_active_scoping_method(): void
    {
        $tenant = new Tenant;

        // Active tenant filtering method exists
        $this->assertTrue(method_exists($tenant, 'scopeActive'));
    }
}
