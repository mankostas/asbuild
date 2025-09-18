<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Mail\ClientWelcomeMail;
use App\Models\Client;
use App\Support\ListQuery;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Mail;

class ClientController extends Controller
{
    use ListQuery;

    public function index(Request $request)
    {
        $this->authorize('viewAny', Client::class);

        $query = Client::query();

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

        $result = $this->listQuery($query, $request, ['name', 'email', 'phone'], ['name', 'created_at']);

        return ClientResource::collection($result['data'])->additional([
            'meta' => $result['meta'],
        ]);
    }

    public function store(ClientRequest $request)
    {
        $this->authorize('create', Client::class);

        $shouldNotify = $request->boolean('notify_client');
        $data = $request->validated();
        $tenantId = $request->determineTargetTenant();

        if ($tenantId === null) {
            throw ValidationException::withMessages([
                'tenant_id' => ['The tenant field is required.'],
            ]);
        }

        $data['tenant_id'] = $tenantId;
        $client = Client::create($data);

        if ($shouldNotify && $client->email) {
            Mail::to($client->email)->send(new ClientWelcomeMail($client));
        }

        return (new ClientResource($client))->response()->setStatusCode(201);
    }

    public function show(Client $client)
    {
        $this->authorize('view', $client);

        return new ClientResource($client);
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

        $client->fill($data);
        $client->save();

        return new ClientResource($client);
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

        return new ClientResource($model);
    }

    public function archive(Client $client)
    {
        $this->authorize('archive', $client);

        $client->archived_at = now();
        $client->save();

        return new ClientResource($client);
    }

    public function unarchive(Client $client)
    {
        $this->authorize('archive', $client);

        $client->archived_at = null;
        $client->save();

        return new ClientResource($client);
    }
}
