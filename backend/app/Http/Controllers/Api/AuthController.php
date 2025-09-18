<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Tenant;
use App\Models\User;
use App\Services\AbilityService;
use App\Services\PermittedClientResolver;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Validation\Rules\Password as PasswordRule;
use Illuminate\Validation\ValidationException;
use Laravel\Sanctum\PersonalAccessToken;

class AuthController extends Controller
{
    public function __construct(private AbilityService $abilityService, private PermittedClientResolver $clientResolver)
    {
    }

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

        $validNames = ['refresh-token', 'impersonation-refresh'];
        if (! $token || ! in_array($token->name, $validNames, true)) {
            return response()->json(['message' => 'invalid_token'], 401);
        }

        $user = $token->tokenable;
        $refreshName = $token->name;
        $accessName = $refreshName === 'impersonation-refresh' ? 'impersonation' : 'access-token';
        $token->delete();

        $accessToken = $user->createToken($accessName, ['*'], now()->addMinutes(15));
        $refreshToken = $user->createToken($refreshName, ['refresh'], now()->addDays(30));

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
            'abilities' => $this->abilityService->resolveAbilities($user),
            'features' => $features,
            'permitted_client_ids' => $this->clientResolver->resolve($user),
        ]);
    }
}
