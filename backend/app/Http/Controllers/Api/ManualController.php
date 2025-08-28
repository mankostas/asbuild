<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Manual;
use App\Services\FileStorageService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use App\Http\Resources\ManualResource;

class ManualController extends Controller
{
    protected function ensureAdmin(Request $request): void
    {
        if (! $request->user()->hasRole('ClientAdmin') && ! $request->user()->hasRole('SuperAdmin')) {
            abort(403);
        }
    }

    public function index(Request $request)
    {
        $tenantId = $request->user()->tenant_id;
        $search = $request->query('q', '');
        $perPage = $request->query('per_page', 15);
        $page = $request->query('page', 1);
        $cacheKey = "manuals:{$tenantId}:{$search}:{$page}:{$perPage}";

        $manuals = Cache::remember($cacheKey, 60, function () use ($request, $perPage) {
            return Manual::where('tenant_id', $request->user()->tenant_id)
                ->with('file')
                ->when($request->query('q'), function ($query, $q) {
                    $query->where(function ($q2) use ($q) {
                        $q2->where('category', 'like', "%{$q}%")
                            ->orWhereJsonContains('tags', $q);
                    });
                })
                ->paginate($perPage);
        });

        return ManualResource::collection($manuals->items())->additional([
            'meta' => [
                'page' => $manuals->currentPage(),
                'per_page' => $manuals->perPage(),
                'total' => $manuals->total(),
            ],
        ]);
    }

    public function store(Request $request, FileStorageService $storage)
    {
        $this->authorize('create', Manual::class);
        $this->ensureAdmin($request);

        $data = $request->validate([
            'file' => 'required|file|mimes:' . implode(',', config('security.allowed_upload_mimes')) . '|max:' . config('security.max_upload_size'),
            'category' => 'nullable|string',
            'tags' => 'array',
            'tags.*' => 'string',
        ]);

        $file = $storage->store($request->file('file'));

        $manual = Manual::create([
            'tenant_id' => $request->user()->tenant_id,
            'file_id' => $file->id,
            'category' => $data['category'] ?? null,
            'tags' => $data['tags'] ?? [],
        ]);

        return (new ManualResource($manual->load('file')))
            ->response()
            ->setStatusCode(201);
    }

    public function show(Manual $manual)
    {
        $this->authorize('view', $manual);

        $cacheKey = "manual:{$manual->id}";
        $data = Cache::remember($cacheKey, 60, function () use ($manual) {
            return $manual->load('file');
        });

        return new ManualResource($data);
    }

    public function update(Request $request, Manual $manual)
    {
        $this->authorize('update', $manual);
        $this->ensureAdmin($request);

        $data = $request->validate([
            'category' => 'nullable|string',
            'tags' => 'array',
            'tags.*' => 'string',
        ]);

        $manual->fill($data);
        $manual->save();

        return new ManualResource($manual);
    }

    public function destroy(Request $request, Manual $manual)
    {
        $this->authorize('delete', $manual);
        $this->ensureAdmin($request);
        $manual->delete();
        return response()->json(['message' => 'deleted']);
    }

    public function replace(Request $request, Manual $manual, FileStorageService $storage)
    {
        $this->authorize('update', $manual);
        $this->ensureAdmin($request);

        $data = $request->validate([
            'file' => 'required|file|mimes:' . implode(',', config('security.allowed_upload_mimes')) . '|max:' . config('security.max_upload_size'),
        ]);

        $file = $storage->store($request->file('file'));
        $manual->file_id = $file->id;
        $manual->updated_at = now();
        $manual->save();

        return new ManualResource($manual->load('file'));
    }

    public function download(Manual $manual, FileStorageService $storage)
    {
        $this->authorize('view', $manual);
        return $storage->stream($manual->file, 'original');
    }
}
