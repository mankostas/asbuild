<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Client;
use App\Models\User;
use App\Support\ListQuery;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class ClientController extends Controller
{
    use ListQuery;

    public function index(Request $request)
    {
        $this->authorize('viewAny', Client::class);

        $query = Client::query()->with(['owner']);

        if ($request->user()->isSuperAdmin()) {
            if ($request->filled('tenant_id')) {
                $query->where('tenant_id', (int) $request->input('tenant_id'));
            } elseif ($request->attributes->get('tenant_id')) {
                $query->where('tenant_id', (int) $request->attributes->get('tenant_id'));
            }
        } else {
            $query->where('tenant_id', (int) $request->attributes->get('tenant_id'));
        }

        $archived = $request->query('archived');
        if (in_array($archived, ['only', 'true', '1'], true)) {
            $query->whereNotNull('archived_at');
        } elseif ($archived !== 'all') {
            $query->whereNull('archived_at');
        }

        $trashed = $request->query('trashed');
        if ($trashed === 'with') {
            $query->withTrashed();
        } elseif ($trashed === 'only') {
            $query->onlyTrashed();
        }

        if ($ownerId = $request->query('owner_id')) {
            $query->where('user_id', (int) $ownerId);
        }

        $result = $this->listQuery($query, $request, ['name', 'email', 'phone'], ['name', 'created_at']);

        return ClientResource::collection($result['data'])->additional([
            'meta' => $result['meta'],
        ]);
    }

    public function store(ClientRequest $request)
    {
        $this->authorize('create', Client::class);

        $data = $request->validated();
        $tenantId = $request->determineTargetTenant();

        if ($tenantId === null) {
            throw ValidationException::withMessages([
                'tenant_id' => ['The tenant field is required.'],
            ]);
        }

        $data['tenant_id'] = $tenantId;
        $ownerId = $data['owner_id'] ?? null;

        if (! $ownerId && $request->user()->tenant_id === $tenantId) {
            $ownerId = $request->user()->id;
        }

        $data['user_id'] = $ownerId;
        unset($data['owner_id']);

        $client = Client::create($data);
        $client->load('owner');

        return (new ClientResource($client))->response()->setStatusCode(201);
    }

    public function show(Client $client)
    {
        $this->authorize('view', $client);

        return new ClientResource($client->load('owner'));
    }

    public function update(ClientRequest $request, Client $client)
    {
        $this->authorize('update', $client);

        $data = $request->validated();
        $tenantId = $client->tenant_id;

        $tenantChanged = false;

        if ($request->user()->isSuperAdmin() && array_key_exists('tenant_id', $data)) {
            $newTenant = (int) $data['tenant_id'];
            $tenantChanged = $newTenant !== (int) $tenantId;
            $tenantId = $newTenant;
        }

        $data['tenant_id'] = $tenantId;

        if (array_key_exists('owner_id', $data)) {
            $data['user_id'] = $data['owner_id'];
        } elseif ($tenantChanged) {
            $data['user_id'] = null;
        } elseif (! $client->user_id && $request->user()->tenant_id === $tenantId) {
            $data['user_id'] = $request->user()->id;
        }

        unset($data['owner_id']);

        $client->fill($data);
        $client->save();

        return new ClientResource($client->load('owner'));
    }

    public function destroy(Client $client)
    {
        $this->authorize('delete', $client);

        $client->delete();

        return response()->json(['message' => 'deleted']);
    }

    public function restore(int $client)
    {
        $model = Client::withTrashed()->findOrFail($client);
        $this->authorize('restore', $model);

        $model->restore();

        return new ClientResource($model->load('owner'));
    }

    public function archive(Client $client)
    {
        $this->authorize('archive', $client);

        $client->archived_at = now();
        $client->save();

        return new ClientResource($client->load('owner'));
    }

    public function unarchive(Client $client)
    {
        $this->authorize('archive', $client);

        $client->archived_at = null;
        $client->save();

        return new ClientResource($client->load('owner'));
    }

    public function transfer(Request $request, Client $client)
    {
        $this->authorize('transfer', $client);

        $data = $request->validate([
            'owner_id' => ['nullable', 'integer', Rule::exists('users', 'id')],
        ]);

        $ownerId = $data['owner_id'] ?? null;
        if ($ownerId) {
            $owner = User::find($ownerId);
            if (! $owner || (int) $owner->tenant_id !== (int) $client->tenant_id) {
                return response()->json([
                    'message' => 'The selected owner is invalid.',
                    'errors' => ['owner_id' => ['The selected owner is invalid.']],
                ], 422);
            }
        }

        $client->user_id = $ownerId;
        $client->save();

        return new ClientResource($client->load('owner'));
    }
}
