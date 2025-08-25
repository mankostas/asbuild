<?php

use PHPUnit\Framework\TestCase;
use App\Models\Appointment;
use App\Models\Tenant;

class AppointmentTest extends TestCase
{
    public function test_appointment_has_tenant_relationship(): void
    {
        $this->assertTrue(method_exists(Appointment::class, 'tenant'));
    }

    public function test_tenant_has_appointments_relationship(): void
    {
        $this->assertTrue(method_exists(Tenant::class, 'appointments'));
    }
}
