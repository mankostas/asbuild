<?php

namespace Tests\Feature;

use Tests\TestCase;

class TypePermissionsI18nTest extends TestCase
{
    public function test_select_tenant_message_translations_exist(): void
    {
        $this->assertEquals(
            'Επιλέξτε μισθωτή για να ορίσετε δικαιώματα',
            trans('types.selectTenantToSetPermissions', [], 'el')
        );
        $this->assertEquals(
            'Select tenant to set permissions',
            trans('types.selectTenantToSetPermissions', [], 'en')
        );
    }
}
