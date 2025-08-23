<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class SettingsController extends Controller
{
    public function getBranding(Request $request)
    {
        $branding = json_decode(config('tenant.branding') ?? '{}', true);
        return response()->json($branding);
    }

    public function updateBranding(Request $request)
    {
        $data = $request->validate([
            'name' => 'nullable|string',
            'color' => 'nullable|string',
            'logo' => 'nullable|string',
            'email_from' => 'nullable|email',
        ]);

        $tenantId = app('tenant_id');
        $current = json_decode(config('tenant.branding') ?? '{}', true);
        $branding = array_merge($current, $data);

        DB::table('tenant_settings')->updateOrInsert(
            ['tenant_id' => $tenantId, 'key' => 'branding'],
            [
                'value' => json_encode($branding),
                'updated_at' => now(),
                'created_at' => now(),
            ]
        );

        config(['tenant.branding' => json_encode($branding)]);

        return response()->json($branding);
    }

    public function updateProfile(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string',
            'email' => 'required|email',
            'password' => 'nullable|confirmed|min:8',
        ]);

        $user = $request->user();
        $user->name = $data['name'];
        $user->email = $data['email'];
        if (! empty($data['password'])) {
            $user->password = Hash::make($data['password']);
        }
        $user->save();

        return response()->json($user);
    }
}

