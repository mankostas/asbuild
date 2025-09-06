<?php

namespace Tests\Feature;

use App\Services\FormSchemaService;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class TaskPhotoRepeaterValidationTest extends TestCase
{
    private array $schema = [
        'sections' => [
            [
                'key' => 'main',
                'label' => 'Main',
                'photos' => [
                    [
                        'key' => 'pr',
                        'label' => 'Photos',
                        'type' => 'photo_repeater',
                        'maxCount' => 2,
                        'validations' => [
                            'required' => true,
                            'mime' => ['image/jpeg'],
                            'size' => 1024,
                        ],
                    ],
                ],
            ],
        ],
    ];

    public function test_repeater_empty_throws_required(): void
    {
        $service = new FormSchemaService();
        try {
            $service->validateData($this->schema, ['pr' => []]);
            $this->fail('Expected ValidationException not thrown');
        } catch (ValidationException $e) {
            $this->assertSame('required', $e->errors()['form_data.pr'][0]);
        }
    }

    public function test_repeater_exceeds_max_count(): void
    {
        $service = new FormSchemaService();
        $data = [
            'pr' => [
                ['mime' => 'image/jpeg', 'size' => 100],
                ['mime' => 'image/jpeg', 'size' => 100],
                ['mime' => 'image/jpeg', 'size' => 100],
            ],
        ];
        try {
            $service->validateData($this->schema, $data);
            $this->fail('Expected ValidationException not thrown');
        } catch (ValidationException $e) {
            $this->assertSame('max_count', $e->errors()['form_data.pr'][0]);
        }
    }

    public function test_repeater_item_validation(): void
    {
        $service = new FormSchemaService();
        $data = [
            'pr' => [
                ['mime' => 'image/png', 'size' => 100],
            ],
        ];
        try {
            $service->validateData($this->schema, $data);
            $this->fail('Expected ValidationException not thrown');
        } catch (ValidationException $e) {
            $this->assertSame('mime', $e->errors()['form_data.pr'][0]);
        }

        $service->validateData($this->schema, [
            'pr' => [
                ['mime' => 'image/jpeg', 'size' => 100],
            ],
        ]);
        $this->assertTrue(true);
    }
}

