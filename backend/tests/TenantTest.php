<?php

use PHPUnit\Framework\TestCase;
use App\Models\Tenant;
use App\Http\Controllers\Api\TenantController;

class TenantTest extends TestCase
{
    public function test_tenant_has_contact_fields(): void
    {
        $tenant = new Tenant();
        $this->assertContains('phone', $tenant->getFillable());
        $this->assertContains('address', $tenant->getFillable());
    }

    public function test_tenant_controller_has_show_method(): void
    {
        $this->assertTrue(method_exists(TenantController::class, 'show'));
    }
}
