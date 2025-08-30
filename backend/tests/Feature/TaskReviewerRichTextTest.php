<?php

namespace Tests\Feature;

use App\Services\FormSchemaService;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class TaskReviewerRichTextTest extends TestCase
{
    public function test_map_reviewer_maps_payload(): void
    {
        $schema = [
            'sections' => [
                [
                    'key' => 'main',
                    'label' => 'Main',
                    'fields' => [
                        ['key' => 'rev', 'label' => 'Reviewer', 'type' => 'reviewer'],
                    ],
                ],
            ],
        ];
        $payload = ['rev' => ['kind' => 'employee', 'id' => 5]];
        $service = new FormSchemaService();
        $service->mapReviewer($schema, $payload);
        $this->assertSame(\App\Models\User::class, $payload['reviewer_type']);
        $this->assertSame(5, $payload['reviewer_id']);
        $this->assertArrayNotHasKey('rev', $payload);
    }

    public function test_map_reviewer_invalid_kind(): void
    {
        $this->expectException(ValidationException::class);
        $schema = [
            'sections' => [[
                'key' => 's',
                'label' => 'S',
                'fields' => [[ 'key' => 'r', 'label' => 'R', 'type' => 'reviewer' ]],
            ]],
        ];
        $payload = ['r' => ['kind' => 'wrong', 'id' => 1]];
        $service = new FormSchemaService();
        $service->mapReviewer($schema, $payload);
    }

    public function test_sanitize_rich_text(): void
    {
        $schema = [
            'sections' => [
                [
                    'key' => 's',
                    'label' => 'S',
                    'fields' => [
                        ['key' => 'rt', 'label' => 'RT', 'type' => 'richtext'],
                    ],
                ],
            ],
        ];
        $data = ['rt' => '<script>alert(1)</script><p>ok</p>'];
        $service = new FormSchemaService();
        $service->sanitizeRichText($schema, $data);
        $this->assertEquals('<p>ok</p>', $data['rt']);
    }
}
