<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\TaskType;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;
use App\Support\PublicIdGenerator;

class TaskTypeI18nTest extends TestCase
{
    use RefreshDatabase;

    public function test_schema_labels_normalize_to_i18n(): void
    {
        $tenant = Tenant::create([
            'public_id' => PublicIdGenerator::generate(),
            'name' => 'T', 'features' => ['tasks']
        ]);
        $role = Role::create([
            'public_id' => PublicIdGenerator::generate(),
            'name' => 'Admin',
            'slug' => 'admin',
            'tenant_id' => $tenant->id,
            'abilities' => ['task_types.manage'],
            'level' => 1,
        ]);
        $user = User::create([
            'public_id' => PublicIdGenerator::generate(),
            'name' => 'U',
            'email' => 'u@example.com',
            'password' => Hash::make('secret'),
            'tenant_id' => $tenant->id,
            'phone' => '123456',
            'address' => 'Street 1',
        ]);
        $user->roles()->attach($role->id, ['tenant_id' => $tenant->id]);
        Sanctum::actingAs($user);

        $type = TaskType::create([
            'public_id' => PublicIdGenerator::generate(),
            'name' => 'Type',
            'tenant_id' => $tenant->id,
            'schema_json' => [
                'sections' => [[
                    'key' => 's1',
                    'label' => 'Section',
                    'fields' => [[
                        'key' => 'f1',
                        'label' => 'Field',
                        'placeholder' => 'Ph',
                        'help' => 'Hp',
                        'type' => 'text',
                    ]],
                ]],
            ],
        ]);

        $service = app(\App\Services\FormSchemaService::class);
        $normalized = $service->normalizeSchema($type->schema_json);
        $section = $normalized['sections'][0];
        $field = $section['fields'][0];
        $this->assertEquals('Section', $section['label']['en']);
        $this->assertEquals('Field', $field['label']['el']);
        $this->assertEquals('Ph', $field['placeholder']['en']);
        $this->assertEquals('Hp', $field['help']['el']);
    }
}
