<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password as PasswordRule;

class SettingsController extends Controller
{
    protected function ensureAdmin(Request $request): void
    {
        if (! $request->user()->hasRole('ClientAdmin') && ! $request->user()->hasRole('SuperAdmin')) {
            abort(403);
        }
    }

    protected function ensureSuperAdmin(Request $request): void
    {
        if (! $request->user()->hasRole('SuperAdmin')) {
            abort(403);
        }
    }

    public function getBranding(Request $request)
    {
        $this->ensureAdmin($request);
        $branding = json_decode(config('tenant.branding') ?? '{}', true);
        return response()->json($branding);
    }

    public function updateBranding(Request $request)
    {
        $this->ensureAdmin($request);
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

    public function getFooter(Request $request)
    {
        $this->ensureSuperAdmin($request);
        $footer = config('tenant.footer') ?? '';
        return response()->json(['text' => $footer]);
    }

    public function updateFooter(Request $request)
    {
        $this->ensureSuperAdmin($request);
        $data = $request->validate([
            'text' => 'required|string',
        ]);

        $tenantId = app('tenant_id');
        DB::table('tenant_settings')->updateOrInsert(
            ['tenant_id' => $tenantId, 'key' => 'footer'],
            [
                'value' => $data['text'],
                'updated_at' => now(),
                'created_at' => now(),
            ]
        );

        config(['tenant.footer' => $data['text']]);

        return response()->json(['text' => $data['text']]);
    }

    public function getTheme(Request $request)
    {
        $user = $request->user();
        return response()->json($user?->theme_settings ?? []);
    }

    public function updateTheme(Request $request)
    {
        $user = $request->user();
        $data = $request->all();
        if ($user->theme_settings === $data) {
            return response()->json($user->theme_settings);
        }
        $user->theme_settings = $data;
        $user->save();

        return response()->json($user->theme_settings);
    }

    public function updateProfile(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string',
            'email' => 'required|email',
            'password' => ['nullable', 'confirmed', PasswordRule::min(config('security.password.min_length'))->mixedCase()->numbers()->symbols()],
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

