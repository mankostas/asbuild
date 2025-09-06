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

    public function test_lookup_requires_source(): void
    {
        $service = new FormSchemaService();
        $schema = [
            'sections' => [
                [
                    'key' => 'main',
                    'label' => 'Main',
                    'fields' => [
                        ['key' => 'l1', 'label' => 'L1', 'type' => 'lookup'],
                    ],
                ],
            ],
        ];
        $this->expectException(ValidationException::class);
        $service->validate($schema);
    }

    public function test_computed_references_must_exist(): void
    {
        $service = new FormSchemaService();
        $schema = [
            'sections' => [
                [
                    'key' => 'main',
                    'label' => 'Main',
                    'fields' => [
                        ['key' => 'a', 'label' => 'A', 'type' => 'number'],
                        ['key' => 'c', 'label' => 'C', 'type' => 'computed', 'expr' => 'a + b'],
                    ],
                ],
            ],
        ];
        $this->expectException(ValidationException::class);
        $service->validate($schema);
    }

    public function test_photo_fields_validation(): void
    {
        $service = new FormSchemaService();
        $schema = [
            'sections' => [
                [
                    'key' => 'main',
                    'label' => 'Main',
                    'photos' => [
                        ['key' => 'p1', 'label' => 'P1', 'type' => 'photo_repeater', 'maxCount' => 0],
                    ],
                ],
            ],
        ];
        $this->expectException(ValidationException::class);
        $service->validate($schema);
    }

    public function test_photo_keys_must_be_unique(): void
    {
        $service = new FormSchemaService();
        $schema = [
            'sections' => [
                [
                    'key' => 'main',
                    'label' => 'Main',
                    'fields' => [
                        ['key' => 'f1', 'label' => 'F1', 'type' => 'text'],
                    ],
                    'photos' => [
                        ['key' => 'f1', 'label' => 'P1', 'type' => 'photo_single'],
                    ],
                ],
            ],
        ];
        $this->expectException(ValidationException::class);
        $service->validate($schema);
    }
}
