<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Manual;
use App\Services\FileStorageService;
use Illuminate\Http\Request;

class ManualController extends Controller
{
    public function index(Request $request)
    {
        $manuals = Manual::where('tenant_id', $request->user()->tenant_id)
            ->with('file')
            ->when($request->query('q'), function ($query, $q) {
                $query->where(function ($q2) use ($q) {
                    $q2->where('category', 'like', "%{$q}%")
                        ->orWhereJsonContains('tags', $q);
                });
            })
            ->get();

        return response()->json($manuals);
    }

    public function store(Request $request, FileStorageService $storage)
    {
        $this->authorize('create', Manual::class);

        $data = $request->validate([
            'file' => 'required|file',
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

        return response()->json($manual->load('file'), 201);
    }

    public function show(Manual $manual)
    {
        $this->authorize('view', $manual);
        return response()->json($manual->load('file'));
    }

    public function update(Request $request, Manual $manual)
    {
        $this->authorize('update', $manual);

        $data = $request->validate([
            'category' => 'nullable|string',
            'tags' => 'array',
            'tags.*' => 'string',
        ]);

        $manual->fill($data);
        $manual->save();

        return response()->json($manual);
    }

    public function destroy(Manual $manual)
    {
        $this->authorize('delete', $manual);
        $manual->delete();
        return response()->json(['message' => 'deleted']);
    }

    public function replace(Request $request, Manual $manual, FileStorageService $storage)
    {
        $this->authorize('update', $manual);

        $data = $request->validate([
            'file' => 'required|file',
        ]);

        $file = $storage->store($request->file('file'));
        $manual->file_id = $file->id;
        $manual->updated_at = now();
        $manual->save();

        return response()->json($manual->load('file'));
    }

    public function download(Manual $manual, FileStorageService $storage)
    {
        $this->authorize('view', $manual);
        return $storage->stream($manual->file, 'original');
    }
}
