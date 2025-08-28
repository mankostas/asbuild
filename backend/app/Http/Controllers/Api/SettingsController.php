<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password as PasswordRule;

class SettingsController extends Controller
{
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

