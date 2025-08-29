<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Validation\Rules\Password as PasswordRule;
use Illuminate\Validation\ValidationException;
use Laravel\Sanctum\PersonalAccessToken;

class AuthController extends Controller
{
    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        $user = User::where('email', $request->email)->first();

        if (! $user || ! Hash::check($request->password, $user->password)) {
            throw ValidationException::withMessages([
                'email' => [__('auth.failed')],
            ]);
        }

        // Placeholder for two-factor authentication hook
        if (method_exists($user, 'requiresTwoFactor') && $user->requiresTwoFactor()) {
            return response()->json(['message' => 'two_factor_required'], 423);
        }

        $user->tokens()->delete();

        $accessToken = $user->createToken('access-token', ['*'], now()->addMinutes(15));
        $refreshToken = $user->createToken('refresh-token', ['refresh'], now()->addDays(30));

        return response()->json([
            'access_token' => $accessToken->plainTextToken,
            'refresh_token' => $refreshToken->plainTextToken,
            'user' => $user->load('roles'),
        ]);
    }

    public function logout(Request $request)
    {
        $token = $request->user()?->currentAccessToken();

        if ($token) {
            $token->delete();
        }

        return response()->json(['message' => 'logged_out']);
    }

    public function refresh(Request $request)
    {
        $request->validate([
            'refresh_token' => 'required',
        ]);

        $token = PersonalAccessToken::findToken($request->refresh_token);

        if (! $token || $token->name !== 'refresh-token') {
            return response()->json(['message' => 'invalid_token'], 401);
        }

        $user = $token->tokenable;
        $token->delete();

        $accessToken = $user->createToken('access-token', ['*'], now()->addMinutes(15));
        $refreshToken = $user->createToken('refresh-token', ['refresh'], now()->addDays(30));

        return response()->json([
            'access_token' => $accessToken->plainTextToken,
            'refresh_token' => $refreshToken->plainTextToken,
        ]);
    }

    public function sendResetLinkEmail(Request $request)
    {
        $request->validate(['email' => 'required|email']);
        Password::sendResetLink($request->only('email'));

        return response()->json(['message' => 'link_sent']);
    }

    public function reset(Request $request)
    {
        $request->validate([
            'token' => 'required',
            'email' => 'required|email',
            'password' => ['required', 'confirmed', PasswordRule::min(config('security.password.min_length'))->mixedCase()->numbers()->symbols()],
        ]);

        $status = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function ($user, $password) {
                $user->forceFill([
                    'password' => Hash::make($password),
                ])->save();

                // Placeholder for two-factor reset hook
                if (method_exists($user, 'clearTwoFactorRecoveryCodes')) {
                    $user->clearTwoFactorRecoveryCodes();
                }
            }
        );

        if ($status != Password::PASSWORD_RESET) {
            return response()->json(['message' => __($status)], 422);
        }

        return response()->json(['message' => 'password_reset']);
    }

    public function me(Request $request)
    {
        $user = $request->user()->load('roles');

        $tenant = Tenant::current() ?? Tenant::find($user->tenant_id);

        $features = $tenant?->features ?? [];
        if ($user->isSuperAdmin()) {
            $features = collect($features)
                ->merge(config('features', []))
                ->unique()
                ->values()
                ->all();
        }

        return response()->json([
            'user' => $user,
            'abilities' => $this->abilitiesFor($user),
            'features' => $features,
        ]);
    }

    protected function abilitiesFor(User $user): array
    {
        if ($user->isSuperAdmin()) {
            return ['*'];
        }

        $tenantId = app()->bound('tenant_id') ? (int) app('tenant_id') : $user->tenant_id;

        $roles = $user->rolesForTenant($tenantId)
            ->merge($user->roles()->wherePivotNull('tenant_id')->get());

        return $roles->pluck('abilities')->flatten()->filter()->unique()->values()->all();
    }
}
