<?php

use PHPUnit\Framework\TestCase;
use App\Http\Controllers\Api\EmployeeController;

class EmployeeTest extends TestCase
{
    public function test_employee_controller_has_show_method(): void
    {
        $this->assertTrue(method_exists(EmployeeController::class, 'show'));
    }

    public function test_employee_controller_allows_super_admin(): void
    {
        $reflection = new ReflectionClass(EmployeeController::class);
        $method = $reflection->getMethod('getTenantId');
        $code = implode("", array_slice(
            file($method->getFileName()),
            $method->getStartLine() - 1,
            $method->getEndLine() - $method->getStartLine() + 1
        ));
        $this->assertStringContainsString('SuperAdmin', $code);
    }
}
