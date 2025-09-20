<?php

namespace Tests\Unit\Http\Requests;

use App\Http\Requests\RoleUpsertRequest;
use App\Http\Requests\TaskStatusUpsertRequest;
use App\Http\Requests\TaskTypeRequest;
use App\Http\Requests\TaskUpsertRequest;
use App\Http\Requests\TeamUpsertRequest;
use App\Http\Requests\TypeUpsertRequest;
use App\Models\Client;
use App\Models\Role;
use App\Models\TaskType;
use App\Models\Team;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Routing\Redirector;
use Illuminate\Support\Str;
use Tests\TestCase;
use App\Support\PublicIdGenerator;

class HashedIdentifierRequestTest extends TestCase
{
    use RefreshDatabase;

    private Tenant $tenant;

    private User $superAdmin;

    private User $regularUser;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tenant = Tenant::create([
            'public_id' => PublicIdGenerator::generate(),
            'name' => 'Acme Inc.'
        ]);

        $this->regularUser = $this->createUser([
            'tenant_id' => $this->tenant->id,
        ]);

        $this->superAdmin = $this->createUser([
            'tenant_id' => $this->tenant->id,
        ]);

        $superRole = Role::create([
            'public_id' => PublicIdGenerator::generate(),
            'name' => 'SuperAdmin',
            'slug' => 'super_admin',
            'level' => 0,
            'abilities' => [],
        ]);

        $this->superAdmin->roles()->attach($superRole);
    }

    public function test_role_upsert_request_translates_tenant_public_id(): void
    {
        $request = RoleUpsertRequest::create('/roles', 'POST', [
            'name' => 'Manager',
            'slug' => 'manager',
            'abilities' => [],
            'tenant_id' => $this->tenant->public_id,
        ]);

        $validated = $this->validateRequest($request, $this->superAdmin);

        $this->assertSame($this->tenant->id, $validated['tenant_id']);
    }

    public function test_team_upsert_request_translates_public_ids(): void
    {
        $lead = $this->createUser(['tenant_id' => $this->tenant->id]);

        $request = TeamUpsertRequest::create('/teams', 'POST', [
            'name' => 'Support',
            'tenant_id' => $this->tenant->public_id,
            'lead_id' => $lead->public_id,
        ]);

        $validated = $this->validateRequest($request, $this->superAdmin);

        $this->assertSame($this->tenant->id, $validated['tenant_id']);
        $this->assertSame($lead->id, $validated['lead_id']);
    }

    public function test_task_type_request_translates_public_ids(): void
    {
        $client = Client::create([
            'public_id' => PublicIdGenerator::generate(),
            'tenant_id' => $this->tenant->id,
            'name' => 'Globex',
            'email' => 'client@example.com',
            'phone' => '1234567890',
        ]);

        $request = TaskTypeRequest::create('/task-types', 'POST', [
            'name' => 'Onboarding',
            'schema_json' => json_encode(['sections' => []]),
            'statuses' => json_encode(['draft']),
            'tenant_id' => $this->tenant->public_id,
            'client_id' => $client->public_id,
        ]);

        $validated = $this->validateRequest($request, $this->superAdmin);

        $this->assertSame($this->tenant->id, $validated['tenant_id']);
        $this->assertSame($client->id, $validated['client_id']);
        $this->assertIsArray($validated['statuses']);
    }

    public function test_task_upsert_request_translates_nested_public_ids(): void
    {
        $taskType = TaskType::create([
            'public_id' => PublicIdGenerator::generate(),
            'tenant_id' => $this->tenant->id,
            'name' => 'General',
            'statuses' => ['open'],
        ]);

        $assignee = $this->createUser(['tenant_id' => $this->tenant->id]);
        $team = Team::create([
            'public_id' => PublicIdGenerator::generate(),
            'tenant_id' => $this->tenant->id,
            'name' => 'QA',
        ]);
        $client = Client::create([
            'public_id' => PublicIdGenerator::generate(),
            'tenant_id' => $this->tenant->id,
            'name' => 'Wayne Enterprises',
            'email' => 'wayne@example.com',
            'phone' => '9876543210',
        ]);

        $request = TaskUpsertRequest::create('/tasks', 'POST', [
            'task_type_id' => $taskType->public_id,
            'assignee' => ['id' => $assignee->public_id],
            'assigned_user_id' => $assignee->public_id,
            'reviewer' => ['kind' => 'team', 'id' => $team->public_id],
            'client_id' => $client->public_id,
        ]);

        $validated = $this->validateRequest($request, $this->regularUser);

        $this->assertSame($taskType->id, $validated['task_type_id']);
        $this->assertSame($assignee->id, $validated['assigned_user_id']);
        $this->assertSame($assignee->id, $validated['assignee']['id']);
        $this->assertSame($team->id, $validated['reviewer']['id']);
        $this->assertSame($client->id, $validated['client_id']);
    }

    public function test_task_status_upsert_request_translates_public_tenant_id(): void
    {
        $request = TaskStatusUpsertRequest::create('/task-statuses', 'POST', [
            'name' => 'In Progress',
            'tenant_id' => $this->tenant->public_id,
        ]);

        $validated = $this->validateRequest($request, $this->superAdmin);

        $this->assertSame($this->tenant->id, $validated['tenant_id']);
        $this->assertStringContainsString((string) $this->tenant->id, $validated['slug']);
    }

    public function test_type_upsert_request_translates_public_tenant_id(): void
    {
        $request = TypeUpsertRequest::create('/types', 'POST', [
            'name' => 'Document',
            'statuses' => json_encode(['draft']),
            'tenant_id' => $this->tenant->public_id,
        ]);

        $validated = $this->validateRequest($request, $this->superAdmin);

        $this->assertSame($this->tenant->id, $validated['tenant_id']);
        $this->assertIsArray($validated['statuses']);
    }

    private function validateRequest(FormRequest $request, User $user, array $routeParameters = []): array
    {
        $request->setContainer(app());
        $request->setRedirector(app(Redirector::class));
        $request->setUserResolver(fn () => $user);
        $request->setRouteResolver(fn () => new class($routeParameters)
        {
            public function __construct(private array $parameters)
            {
            }

            public function parameter(string $key, $default = null)
            {
                return $this->parameters[$key] ?? $default;
            }
        });

        $request->validateResolved();

        return $request->validated();
    }

    private function createUser(array $attributes = []): User
    {
        return User::create(array_merge([
            'name' => 'User ' . Str::random(6),
            'email' => Str::random(10) . '@example.com',
            'password' => bcrypt('password'),
            'tenant_id' => $this->tenant->id,
            'phone' => '1234567890',
            'address' => '123 Main St',
            'type' => 'employee',
            'department' => 'Ops',
            'status' => 'active',
        ], $attributes));
    }
}
