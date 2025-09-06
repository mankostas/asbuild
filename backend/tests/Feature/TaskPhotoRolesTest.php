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

class TaskPhotoRolesTest extends TestCase
{
    use RefreshDatabase;

    private array $schema = [
        'roles' => ['viewer' => 'read'],
        'sections' => [
            [
                'key' => 'main',
                'label' => 'Main',
                'photos' => [
                    ['key' => 'secret', 'label' => 'Secret', 'type' => 'photo_single', 'roles' => ['viewer' => 'hidden']],
                    ['key' => 'note', 'label' => 'Note', 'type' => 'photo_single'],
                ],
            ],
        ],
    ];

    private function makeViewer(): User
    {
        $tenant = Tenant::create(['name' => 'T', 'features' => ['tasks']]);
        $role = Role::create([
            'name' => 'Viewer',
            'slug' => 'viewer',
            'tenant_id' => $tenant->id,
            'abilities' => ['tasks.view'],
            'level' => 5,
        ]);
        $user = User::create([
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
        $photos = $filtered['sections'][0]['photos'];
        $this->assertCount(1, $photos);
        $this->assertSame('note', $photos[0]['key']);
        $this->assertTrue($photos[0]['readOnly']);

        $data = [
            'secret' => ['mime' => 'image/jpeg'],
            'note' => ['mime' => 'image/jpeg'],
        ];
        $filteredData = $service->filterDataForRoles($this->schema, $data, $user);
        $this->assertSame(['note' => ['mime' => 'image/jpeg']], $filteredData);
    }
}

