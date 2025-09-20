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
            'tenant_id' => $tenant->getKey(),
            'name' => 'Manager',
            'slug' => 'manager',
            'level' => 2,
            'abilities' => ['employees.view'],
        ]);
        $employee = $this->createUser([
            'tenant_id' => $tenant->getKey(),
        ]);
        $employee->roles()->attach($role->getKey(), ['tenant_id' => $tenant->getKey()]);

        $resource = new EmployeeResource($employee->fresh()->load('roles.tenant', 'tenant'));
        $data = $resource->toArray(new Request());

        $this->assertSame($employee->public_id, $data['id']);
        $this->assertSame($tenant->public_id, $data['tenant_id']);
        $roleIds = collect($data['roles'])->pluck('id')->all();
        $this->assertContains($role->public_id, $roleIds);
        $this->assertNotContains($role->getKey(), $roleIds);
    }

    public function test_team_resource_uses_public_identifiers(): void
    {
        $tenant = Tenant::create([
            'public_id' => PublicIdGenerator::generate(),
            'name' => 'Tenant'
        ]);
        $lead = $this->createUser(['tenant_id' => $tenant->getKey()]);
        $member = $this->createUser(['tenant_id' => $tenant->getKey()]);

        $team = Team::create([
            'public_id' => PublicIdGenerator::generate(),
            'tenant_id' => $tenant->getKey(),
            'name' => 'Support',
            'description' => 'Support team',
            'lead_id' => $lead->getKey(),
        ]);
        $team->employees()->attach([$lead->getKey(), $member->getKey()]);

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
            'tenant_id' => $tenant->getKey(), 'name' => 'ACME'
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
            'tenant_id' => $tenant->getKey(),
            'file_id' => $file->getKey(),
            'client_id' => $client->getKey(),
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
        $user = $this->createUser(['tenant_id' => $tenant->getKey()]);
        $notification = Notification::create([
            'public_id' => PublicIdGenerator::generate(),
            'user_id' => $user->getKey(),
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
            'tenant_id' => $tenant->getKey(), 'name' => 'ACME'
        ]);
        $type = TaskType::create([
            'public_id' => PublicIdGenerator::generate(),
            'tenant_id' => $tenant->getKey(),
            'client_id' => $client->getKey(),
            'name' => 'Install',
        ]);

        $request = Request::create('/', 'GET');
        $request->setUserResolver(fn () => $this->createUser(['tenant_id' => $tenant->getKey()]));

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
        $creator = $this->createUser(['tenant_id' => $tenant->getKey()]);
        $assignee = $this->createUser(['tenant_id' => $tenant->getKey()]);
        $client = Client::create([
            'public_id' => PublicIdGenerator::generate(),
            'tenant_id' => $tenant->getKey(), 'name' => 'ACME'
        ]);
        $type = TaskType::create([
            'public_id' => PublicIdGenerator::generate(),
            'tenant_id' => $tenant->getKey(),
            'client_id' => $client->getKey(),
            'name' => 'Install',
        ]);
        $status = TaskStatus::create([
            'public_id' => PublicIdGenerator::generate(),
            'tenant_id' => $tenant->getKey(),
            'name' => 'Open',
            'slug' => TaskStatus::prefixSlug('open', $tenant->getKey()),
            'position' => 1,
        ]);

        $task = Task::create([
            'public_id' => PublicIdGenerator::generate(),
            'tenant_id' => $tenant->getKey(),
            'user_id' => $creator->getKey(),
            'reporter_user_id' => $creator->getKey(),
            'status' => 'open',
            'status_slug' => $status->slug,
            'title' => 'Install router',
            'task_type_id' => $type->getKey(),
            'client_id' => $client->getKey(),
            'assigned_user_id' => $assignee->getKey(),
        ]);
        TaskWatcher::create([
            'public_id' => PublicIdGenerator::generate(),
            'task_id' => $task->getKey(),
            'user_id' => $creator->getKey(),
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
            'tenant_id' => $tenant->getKey(),
            'type' => 'tenant',
        ]);
        $owner->roles()->attach($role->getKey(), ['tenant_id' => $tenant->getKey()]);

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
            'tenant_id' => $tenant->getKey(),
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
        $tenantId = $attributes['tenant_id'] ?? null;

        if ($tenantId === null) {
            $tenant = Tenant::create([
                'public_id' => PublicIdGenerator::generate(),
                'name' => 'Tenant ' . uniqid(),
            ]);

            $tenantId = $tenant->getKey();
        }

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
