<?php

namespace Tests\Unit\Http\Resources;

use App\Http\Controllers\Api\ClientResource as ApiClientResource;
use App\Http\Resources\EmployeeResource;
use App\Http\Resources\ManualResource;
use App\Http\Resources\NotificationResource;
use App\Http\Resources\TaskResource;
use App\Http\Resources\TaskTypeResource;
use App\Http\Resources\TeamResource;
use App\Http\Resources\TenantOwnerResource;
use App\Models\Client;
use App\Models\File;
use App\Models\Manual;
use App\Models\Notification;
use App\Models\Role;
use App\Models\Task;
use App\Models\TaskStatus;
use App\Models\TaskType;
use App\Models\TaskWatcher;
use App\Models\Team;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;
use App\Support\PublicIdGenerator;

class ResourceSerializationTest extends TestCase
{
    use RefreshDatabase;

    public function test_employee_resource_uses_public_identifiers(): void
    {
        $tenant = Tenant::create([
            'public_id' => PublicIdGenerator::generate(),
            'name' => 'Tenant'
        ]);
        $role = Role::create([
            'public_id' => PublicIdGenerator::generate(),
            'tenant_id' => $tenant->id,
            'name' => 'Manager',
            'slug' => 'manager',
            'level' => 2,
            'abilities' => ['employees.view'],
        ]);
        $employee = $this->createUser([
            'tenant_id' => $tenant->id,
        ]);
        $employee->roles()->attach($role->id, ['tenant_id' => $tenant->id]);

        $resource = new EmployeeResource($employee->fresh()->load('roles.tenant', 'tenant'));
        $data = $resource->toArray(new Request());

        $this->assertSame($employee->public_id, $data['id']);
        $this->assertSame($tenant->public_id, $data['tenant_id']);
        $roleIds = collect($data['roles'])->pluck('id')->all();
        $this->assertContains($role->public_id, $roleIds);
        $this->assertNotContains($role->id, $roleIds);
    }

    public function test_team_resource_uses_public_identifiers(): void
    {
        $tenant = Tenant::create([
            'public_id' => PublicIdGenerator::generate(),
            'name' => 'Tenant'
        ]);
        $lead = $this->createUser(['tenant_id' => $tenant->id]);
        $member = $this->createUser(['tenant_id' => $tenant->id]);

        $team = Team::create([
            'public_id' => PublicIdGenerator::generate(),
            'tenant_id' => $tenant->id,
            'name' => 'Support',
            'description' => 'Support team',
            'lead_id' => $lead->id,
        ]);
        $team->employees()->attach([$lead->id, $member->id]);

        $resource = new TeamResource($team->load(['tenant', 'lead', 'employees']));
        $data = $resource->toArray(new Request());

        $this->assertSame($team->public_id, $data['id']);
        $this->assertSame($tenant->public_id, $data['tenant_id']);
        $this->assertSame($lead->public_id, $data['lead_id']);
        $employeeIds = collect($data['employees'])->pluck('id')->all();
        $this->assertEqualsCanonicalizing([
            $lead->public_id,
            $member->public_id,
        ], $employeeIds);
    }

    public function test_manual_resource_includes_public_identifiers(): void
    {
        $tenant = Tenant::create([
            'public_id' => PublicIdGenerator::generate(),
            'name' => 'Tenant'
        ]);
        $client = Client::create([
            'public_id' => PublicIdGenerator::generate(),
            'tenant_id' => $tenant->id, 'name' => 'ACME'
        ]);
        $file = File::create([
            'public_id' => PublicIdGenerator::generate(),
            'path' => 'manuals/file.pdf',
            'filename' => 'file.pdf',
            'mime_type' => 'application/pdf',
            'size' => 1024,
        ]);
        $manual = Manual::create([
            'public_id' => PublicIdGenerator::generate(),
            'tenant_id' => $tenant->id,
            'file_id' => $file->id,
            'client_id' => $client->id,
            'category' => 'safety',
            'tags' => ['alpha'],
        ]);

        $resource = new ManualResource($manual->load(['tenant', 'client', 'file']));
        $data = $resource->toArray(new Request());

        $this->assertSame($manual->public_id, $data['id']);
        $this->assertSame($tenant->public_id, $data['tenant_id']);
        $this->assertSame($client->public_id, $data['client_id']);
        $this->assertSame($file->public_id, $data['file']['id']);
        $this->assertArrayNotHasKey('file_id', $data);
    }

    public function test_notification_resource_uses_public_id(): void
    {
        $tenant = Tenant::create([
            'public_id' => PublicIdGenerator::generate(),
            'name' => 'Notifications'
        ]);
        $user = $this->createUser(['tenant_id' => $tenant->id]);
        $notification = Notification::create([
            'public_id' => PublicIdGenerator::generate(),
            'user_id' => $user->id,
            'category' => 'general',
            'message' => 'Welcome',
        ]);

        $resource = new NotificationResource($notification);
        $data = $resource->toArray(new Request());

        $this->assertSame($notification->public_id, $data['id']);
        $this->assertArrayNotHasKey('user_id', $data);
    }

    public function test_task_type_resource_converts_identifiers(): void
    {
        $tenant = Tenant::create([
            'public_id' => PublicIdGenerator::generate(),
            'name' => 'Tenant'
        ]);
        $client = Client::create([
            'public_id' => PublicIdGenerator::generate(),
            'tenant_id' => $tenant->id, 'name' => 'ACME'
        ]);
        $type = TaskType::create([
            'public_id' => PublicIdGenerator::generate(),
            'tenant_id' => $tenant->id,
            'client_id' => $client->id,
            'name' => 'Install',
        ]);

        $request = Request::create('/', 'GET');
        $request->setUserResolver(fn () => $this->createUser(['tenant_id' => $tenant->id]));

        $resource = new TaskTypeResource($type->load(['tenant', 'client']));
        $data = $resource->toArray($request);

        $this->assertSame($type->public_id, $data['id']);
        $this->assertSame($tenant->public_id, $data['tenant_id']);
        $this->assertSame($client->public_id, $data['client_id']);
        $this->assertSame($client->public_id, $data['client']['id']);
    }

    public function test_task_resource_rewrites_identifier_fields(): void
    {
        $tenant = Tenant::create([
            'public_id' => PublicIdGenerator::generate(),
            'name' => 'Tenant'
        ]);
        $creator = $this->createUser(['tenant_id' => $tenant->id]);
        $assignee = $this->createUser(['tenant_id' => $tenant->id]);
        $client = Client::create([
            'public_id' => PublicIdGenerator::generate(),
            'tenant_id' => $tenant->id, 'name' => 'ACME'
        ]);
        $type = TaskType::create([
            'public_id' => PublicIdGenerator::generate(),
            'tenant_id' => $tenant->id,
            'client_id' => $client->id,
            'name' => 'Install',
        ]);
        $status = TaskStatus::create([
            'public_id' => PublicIdGenerator::generate(),
            'tenant_id' => $tenant->id,
            'name' => 'Open',
            'slug' => TaskStatus::prefixSlug('open', $tenant->id),
            'position' => 1,
        ]);

        $task = Task::create([
            'public_id' => PublicIdGenerator::generate(),
            'tenant_id' => $tenant->id,
            'user_id' => $creator->id,
            'reporter_user_id' => $creator->id,
            'status' => 'open',
            'status_slug' => $status->slug,
            'title' => 'Install router',
            'task_type_id' => $type->id,
            'client_id' => $client->id,
            'assigned_user_id' => $assignee->id,
        ]);
        TaskWatcher::create([
            'public_id' => PublicIdGenerator::generate(),
            'task_id' => $task->id,
            'user_id' => $creator->id,
        ]);

        $task->load(['tenant', 'type.tenant', 'type.client', 'client', 'assignee', 'user', 'reporter', 'watchers.user']);
        $task->loadCount(['comments', 'attachments', 'watchers', 'subtasks']);

        $request = Request::create('/', 'GET');
        $request->setUserResolver(fn () => $creator);

        $resource = new TaskResource($task);
        $data = $resource->toArray($request);

        $this->assertSame($task->public_id, $data['id']);
        $this->assertSame($tenant->public_id, $data['tenant_id']);
        $this->assertSame($type->public_id, $data['task_type_id']);
        $this->assertSame($client->public_id, $data['client_id']);
        $this->assertSame($assignee->public_id, $data['assigned_user_id']);
        $this->assertSame($creator->public_id, $data['user_id']);
        $this->assertSame($creator->public_id, $data['reporter_user_id']);
        $this->assertSame($client->public_id, $data['client']['id']);
        $this->assertSame($assignee->public_id, $data['assignee']['id']);
        $this->assertSame($type->public_id, $data['type']['id']);
        $this->assertSame($creator->public_id, $data['watchers'][0]['user_id']);
    }

    public function test_tenant_owner_resource_converts_identifiers(): void
    {
        $tenant = Tenant::create([
            'public_id' => PublicIdGenerator::generate(),
            'name' => 'Tenant'
        ]);
        $role = $tenant->roles()->where('slug', 'tenant')->first();
        $owner = $this->createUser([
            'tenant_id' => $tenant->id,
            'type' => 'tenant',
        ]);
        $owner->roles()->attach($role->id, ['tenant_id' => $tenant->id]);

        $resource = new TenantOwnerResource($owner->fresh()->load(['roles.tenant', 'tenant']));
        $data = $resource->toArray(new Request());

        $this->assertSame($owner->public_id, $data['id']);
        $this->assertSame($tenant->public_id, $data['tenant_id']);
        $this->assertSame($role->public_id, $data['roles'][0]['id']);
    }

    public function test_client_resource_surfaces_public_id(): void
    {
        $tenant = Tenant::create([
            'public_id' => PublicIdGenerator::generate(),
            'name' => 'Tenant'
        ]);
        $client = Client::create([
            'public_id' => PublicIdGenerator::generate(),
            'tenant_id' => $tenant->id,
            'name' => 'ACME',
            'email' => 'client@example.com',
        ]);

        $resource = new ApiClientResource($client->load('tenant'));
        $data = $resource->toArray(new Request());

        $this->assertSame($client->public_id, $data['id']);
        $this->assertSame($tenant->public_id, $data['tenant_id']);
        $this->assertSame($tenant->public_id, $data['tenant']['id']);
    }

    private function createUser(array $attributes = []): User
    {
        $tenantId = $attributes['tenant_id'] ?? Tenant::create([
            'public_id' => PublicIdGenerator::generate(),
            'name' => 'Tenant ' . uniqid(),
        ])->id;

        $defaults = [
            'public_id' => PublicIdGenerator::generate(),
            'tenant_id' => $tenantId,
            'name' => 'User ' . uniqid(),
            'email' => uniqid('user', true) . '@example.com',
            'password' => Hash::make('secret'),
            'phone' => '1234567890',
            'address' => '123 Main St',
            'type' => 'employee',
            'status' => 'active',
        ];

        return User::create(array_merge($defaults, $attributes));
    }
}
