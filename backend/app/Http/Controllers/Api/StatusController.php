<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Status;
use Illuminate\Http\Request;

class StatusController extends Controller
{
    protected function ensureAdmin(Request $request): void
    {
        if (! $request->user()->hasRole('ClientAdmin') && ! $request->user()->hasRole('SuperAdmin')) {
            abort(403);
        }
    }

    public function index()
    {
        return Status::all();
    }

    public function store(Request $request)
    {
        $this->ensureAdmin($request);
        $data = $request->validate([
            'name' => 'required|string',
        ]);
        $status = Status::create($data);
        return response()->json($status, 201);
    }

    public function show(Status $status)
    {
        return $status;
    }

    public function update(Request $request, Status $status)
    {
        $this->ensureAdmin($request);
        $data = $request->validate([
            'name' => 'required|string',
        ]);
        $status->update($data);
        return $status;
    }

    public function destroy(Request $request, Status $status)
    {
        $this->ensureAdmin($request);
        $status->delete();
        return response()->json(['message' => 'deleted']);
    }
}

