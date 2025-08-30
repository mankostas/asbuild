<?php

namespace Tests\Feature;

use App\Services\FormSchemaService;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class TaskFormDateValidationTest extends TestCase
{
    private array $schema = [
        'sections' => [
            [
                'key' => 'main',
                'label' => 'Main',
                'fields' => [
                    ['key' => 'd', 'label' => 'D', 'type' => 'date'],
                    ['key' => 't', 'label' => 'T', 'type' => 'time'],
                    ['key' => 'dt', 'label' => 'DT', 'type' => 'datetime'],
                    ['key' => 'du', 'label' => 'DU', 'type' => 'duration'],
                ],
            ],
        ],
    ];

    public function test_invalid_values_throw_validation_exception(): void
    {
        $service = new FormSchemaService();
        $this->expectException(ValidationException::class);
        $service->validateData($this->schema, [
            'd' => 'not-date',
            't' => '25:61',
            'dt' => 'bad',
            'du' => 'PT-5M',
        ]);
    }

    public function test_valid_values_pass(): void
    {
        $service = new FormSchemaService();
        $service->validateData($this->schema, [
            'd' => '2024-01-01',
            't' => '12:30',
            'dt' => '2024-01-01T12:30:00Z',
            'du' => 'PT30M',
        ]);
        $this->assertTrue(true);
    }
}
