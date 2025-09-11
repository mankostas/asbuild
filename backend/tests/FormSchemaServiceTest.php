<?php

use PHPUnit\Framework\TestCase;
use App\Services\FormSchemaService;

class FormSchemaServiceTest extends TestCase
{
    protected function validator(): FormSchemaService
    {
        return new class extends FormSchemaService {
            public function check($value)
            {
                return $this->isValidDate($value);
            }
        };
    }

    public function test_accepts_valid_iso_date(): void
    {
        $this->assertTrue($this->validator()->check('2024-02-29'));
    }

    public function test_rejects_invalid_or_relative_dates(): void
    {
        $validator = $this->validator();
        $this->assertFalse($validator->check('2024-02-30'));
        $this->assertFalse($validator->check('next Tuesday'));
    }
}
