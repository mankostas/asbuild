<?php

namespace Tests\Feature;

use App\Services\FormSchemaService;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class TaskTypeSchemaTest extends TestCase
{
    public function test_invalid_schema_missing_sections(): void
    {
        $service = new FormSchemaService();
        $this->expectException(ValidationException::class);
        $service->validate([]);
    }

    public function test_invalid_field_type(): void
    {
        $service = new FormSchemaService();
        $schema = [
            'sections' => [
                [
                    'key' => 'main',
                    'label' => 'Main',
                    'fields' => [
                        ['key' => 'f1', 'label' => 'F1', 'type' => 'unknown'],
                    ],
                ],
            ],
        ];
        $this->expectException(ValidationException::class);
        $service->validate($schema);
    }
}
