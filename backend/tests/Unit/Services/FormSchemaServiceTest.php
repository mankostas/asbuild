<?php

namespace Tests\Unit\Services;

use App\Models\Team;
use App\Models\Tenant;
use App\Models\User;
use App\Support\PublicIdGenerator;
use App\Services\FormSchemaService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class FormSchemaServiceTest extends TestCase
{
    use RefreshDatabase;

    protected Tenant $tenant;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tenant = Tenant::create([
            'name' => 'Acme Inc.',
            'public_id' => PublicIdGenerator::generate(),
        ]);
    }

    public function test_map_assignee_resolves_public_id_from_form_data(): void
    {
        $user = User::create([
            'public_id' => PublicIdGenerator::generate(),
            'name' => 'Jane Doe',
            'email' => 'jane@example.com',
            'password' => bcrypt('password'),
            'tenant_id' => $this->tenant->getKey(),
            'phone' => '1234567890',
            'address' => '123 Main St',
            'type' => 'employee',
            'department' => 'Ops',
            'status' => 'active',
        ]);

        $schema = [
            'sections' => [[
                'fields' => [[
                    'key' => 'assignee_field',
                    'type' => 'assignee',
                ]],
            ]],
        ];

        $payload = [
            'form_data' => [
                'assignee_field' => ['id' => $user->public_id],
            ],
        ];

        $service = app(FormSchemaService::class);
        $service->mapAssignee($schema, $payload);

        $this->assertSame($user->getKey(), $payload['assigned_user_id']);
        $this->assertArrayNotHasKey('assignee_field', $payload['form_data']);
    }

    public function test_map_reviewer_resolves_public_id_from_form_data(): void
    {
        $team = Team::create([
            'public_id' => PublicIdGenerator::generate(),
            'name' => 'Support',
            'tenant_id' => $this->tenant->getKey(),
        ]);

        $schema = [
            'sections' => [[
                'fields' => [[
                    'key' => 'reviewer_field',
                    'type' => 'reviewer',
                ]],
            ]],
        ];

        $payload = [
            'form_data' => [
                'reviewer_field' => [
                    'kind' => 'team',
                    'id' => $team->public_id,
                ],
            ],
        ];

        $service = app(FormSchemaService::class);
        $service->mapReviewer($schema, $payload);

        $this->assertSame(Team::class, $payload['reviewer_type']);
        $this->assertSame($team->getKey(), $payload['reviewer_id']);
        $this->assertArrayNotHasKey('reviewer_field', $payload['form_data']);
    }

    public function test_map_assignee_throws_for_invalid_identifier(): void
    {
        $schema = [
            'sections' => [[
                'fields' => [[
                    'key' => 'assignee_field',
                    'type' => 'assignee',
                ]],
            ]],
        ];

        $payload = [
            'form_data' => [
                'assignee_field' => ['id' => 'invalid'],
            ],
        ];

        $service = app(FormSchemaService::class);

        $this->expectException(ValidationException::class);
        $service->mapAssignee($schema, $payload);
    }
}
