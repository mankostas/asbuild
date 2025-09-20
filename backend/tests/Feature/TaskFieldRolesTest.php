<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\Tenant;
use App\Models\User;
use App\Services\FormSchemaService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;
use Illuminate\Validation\ValidationException;
use App\Support\PublicIdGenerator;

class TaskFieldRolesTest extends TestCase
{
    use RefreshDatabase;

    private array $schema = [
        'roles' => ['viewer' => 'read'],
        'sections' => [
            [
                'key' => 'main',
                'label' => 'Main',
                'fields' => [
                    ['key' => 'secret', 'label' => 'Secret', 'type' => 'text', 'roles' => ['viewer' => 'hidden']],
                    ['key' => 'note', 'label' => 'Note', 'type' => 'text'],
                ],
            ],
        ],
    ];

    private function makeViewer(): User
    {
        $tenant = Tenant::create([
            'public_id' => PublicIdGenerator::generate(),
            'name' => 'T', 'features' => ['tasks']
        ]);
        $role = Role::create([
            'public_id' => PublicIdGenerator::generate(),
            'name' => 'Viewer',
            'slug' => 'viewer',
            'tenant_id' => $tenant->id,
            'abilities' => ['tasks.view'],
            'level' => 5,
        ]);
        $user = User::create([
            'public_id' => PublicIdGenerator::generate(),
            'name' => 'U',
            'email' => 'u@example.com',
            'password' => Hash::make('secret'),
            'tenant_id' => $tenant->id,
        ]);
        $user->roles()->attach($role->id, ['tenant_id' => $tenant->id]);
        Sanctum::actingAs($user);
        return $user;
    }

    public function test_schema_and_data_filtered_for_viewer(): void
    {
        $user = $this->makeViewer();
        $service = new FormSchemaService();

        $filtered = $service->filterSchemaForRoles($this->schema, $user);
        $fields = $filtered['sections'][0]['fields'];
        $this->assertCount(1, $fields);
        $this->assertSame('note', $fields[0]['key']);
        $this->assertTrue($fields[0]['readOnly']);

        $data = ['secret' => 'x', 'note' => 'y'];
        $filteredData = $service->filterDataForRoles($this->schema, $data, $user);
        $this->assertSame(['note' => 'y'], $filteredData);
    }

    public function test_assert_can_edit_blocks_read_only(): void
    {
        $user = $this->makeViewer();
        $service = new FormSchemaService();
        $this->expectException(ValidationException::class);
        $service->assertCanEdit($this->schema, ['note' => 'x'], $user);
    }
}
