<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Role;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class EmployeeController extends Controller
{
    protected function ensureAdmin(Request $request): void
    {
        if (! $request->user()->hasRole('ClientAdmin')) {
            abort(403);
        }
    }

    public function index(Request $request)
    {
        $this->ensureAdmin($request);

        return User::where('tenant_id', $request->user()->tenant_id)->with('roles')->get();
    }

    public function store(Request $request)
    {
        $this->ensureAdmin($request);

        $data = $request->validate([
            'name' => 'required|string',
            'email' => 'required|email',
            'roles' => 'array',
        ]);

        $password = Str::random(12);

        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'tenant_id' => $request->user()->tenant_id,
            'password' => Hash::make($password),
        ]);

        if (! empty($data['roles'])) {
            $roles = Role::whereIn('name', $data['roles'])->pluck('id');
            $user->roles()->sync($roles);
        }

        Mail::raw("You have been invited. Temporary password: {$password}", function ($m) use ($user) {
            $m->to($user->email)->subject('Invitation');
        });

        return response()->json($user->load('roles'), 201);
    }

    public function update(Request $request, User $employee)
    {
        $this->ensureAdmin($request);

        $data = $request->validate([
            'name' => 'sometimes|string',
            'roles' => 'array',
        ]);

        if (isset($data['name'])) {
            $employee->name = $data['name'];
        }
        $employee->save();

        if (array_key_exists('roles', $data)) {
            $roles = Role::whereIn('name', $data['roles'])->pluck('id');
            $employee->roles()->sync($roles);
        }

        return $employee->load('roles');
    }

    public function destroy(Request $request, User $employee)
    {
        $this->ensureAdmin($request);
        $employee->delete();
        return response()->noContent();
    }
}

