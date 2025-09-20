<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Mail\ClientWelcomeMail;
use App\Models\Client;
use App\Models\Tenant;
use App\Support\ListQuery;
use App\Support\PublicIdResolver;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Mail;

class ClientController extends Controller
{
    use ListQuery;

    public function __construct(private PublicIdResolver $publicIdResolver)
    {
    }

    public function index(Request $request)
    {
        $this->authorize('viewAny', Client::class);

        $query = Client::query();

        if ($request->user()->isSuperAdmin()) {
            if ($request->filled('tenant_id')) {
                $tenantId = $this->resolveTenantIdentifier($request->input('tenant_id'));

                if ($tenantId === null && $request->input('tenant_id') !== null && $request->input('tenant_id') !== '') {
                    $query->whereRaw('1 = 0');
                } elseif ($tenantId !== null) {
                    $query->where('tenant_id', $tenantId);
                }
            } elseif ($request->attributes->get('tenant_id')) {
                $tenantId = $this->resolveTenantIdentifier($request->attributes->get('tenant_id'));

                if ($tenantId !== null) {
                    $query->where('tenant_id', $tenantId);
                }
            }
        } else {
            $tenantId = $this->resolveTenantIdentifier($request->attributes->get('tenant_id'));

            if ($tenantId !== null) {
                $query->where('tenant_id', $tenantId);
            } else {
                $query->whereRaw('1 = 0');
            }
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

    public function restore(string $client)
    {
        $clientId = $this->publicIdResolver->resolve(Client::class, $client);

        if ($clientId === null) {
            abort(404);
        }

        $model = Client::withTrashed()->findOrFail($clientId);
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

    public function bulkArchive(Request $request)
    {
        $input = $request->all();
        if (isset($input['ids']) && is_array($input['ids'])) {
            $input['ids'] = array_map(static fn ($value) => is_string($value) ? $value : (string) $value, $input['ids']);
        }

        $data = validator($input, [
            'ids' => ['required', 'array'],
            'ids.*' => ['string'],
        ])->validate();

        $normalized = $this->normalizeIdentifiers($data['ids']);
        $ids = $this->resolveClientIdentifiers($normalized);

        if (count($ids) !== count($normalized)) {
            throw ValidationException::withMessages([
                'ids' => ['One or more clients are invalid.'],
            ]);
        }
        $clients = Client::query()->whereIn('id', $ids)->get();

        $clients->each(function (Client $client) {
            $this->authorize('archive', $client);
        });

        $now = now();

        foreach ($clients as $client) {
            if ($client->trashed() || $client->archived_at !== null) {
                continue;
            }

            $client->archived_at = $now;
            $client->save();
        }

        return ClientResource::collection($clients);
    }

    public function bulkDestroy(Request $request)
    {
        $input = $request->all();
        if (isset($input['ids']) && is_array($input['ids'])) {
            $input['ids'] = array_map(static fn ($value) => is_string($value) ? $value : (string) $value, $input['ids']);
        }

        $data = validator($input, [
            'ids' => ['required', 'array'],
            'ids.*' => ['string'],
        ])->validate();

        $normalized = $this->normalizeIdentifiers($data['ids']);
        $ids = $this->resolveClientIdentifiers($normalized);

        if (count($ids) !== count($normalized)) {
            throw ValidationException::withMessages([
                'ids' => ['One or more clients are invalid.'],
            ]);
        }
        $clients = Client::query()->whereIn('id', $ids)->get();

        $clients->each(function (Client $client) {
            $this->authorize('delete', $client);
        });

        if ($clients->isNotEmpty()) {
            Client::query()->whereKey($clients->modelKeys())->delete();
        }

        return response()->json(['message' => 'deleted']);
    }

    public function toggleStatus(Client $client)
    {
        $this->authorize('update', $client);

        $client->status = $client->status === 'active' ? 'inactive' : 'active';
        $client->save();

        return new ClientResource($client);
    }

    private function resolveTenantIdentifier(mixed $identifier): ?int
    {
        if ($identifier instanceof Tenant) {
            return (int) $identifier->getKey();
        }

        if ($identifier === null || $identifier === '') {
            return null;
        }

        return $this->publicIdResolver->resolve(Tenant::class, $identifier);
    }

    private function resolveClientIdentifiers(array $identifiers): array
    {
        $resolved = [];

        foreach ($identifiers as $identifier) {
            if (is_string($identifier)) {
                $identifier = trim($identifier);

                if ($identifier === '') {
                    continue;
                }
            }

            $id = $this->publicIdResolver->resolve(Client::class, $identifier);

            if ($id !== null) {
                $resolved[] = $id;
            }
        }

        return array_values(array_unique($resolved));
    }

    private function normalizeIdentifiers(array $identifiers): array
    {
        $normalized = [];

        foreach ($identifiers as $identifier) {
            if (is_string($identifier)) {
                $identifier = trim($identifier);
            }

            if ($identifier === null || $identifier === '') {
                continue;
            }

            $normalized[] = is_string($identifier) ? $identifier : (string) $identifier;
        }

        return array_values(array_unique($normalized));
    }
}
