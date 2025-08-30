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

    public function test_enum_required_for_choice_fields(): void
    {
        $service = new FormSchemaService();
        foreach (['select', 'multiselect', 'radio', 'checkbox', 'chips'] as $type) {
            $schema = [
                'sections' => [
                    [
                        'key' => 'main',
                        'label' => 'Main',
                        'fields' => [
                            ['key' => 'f1', 'label' => 'F1', 'type' => $type],
                        ],
                    ],
                ],
            ];
            try {
                $service->validate($schema);
                $this->fail('Enum not enforced for ' . $type);
            } catch (ValidationException $e) {
                $this->assertEquals('enum required for choice types', $e->errors()['schema_json'][0]);
            }
        }
    }
}
