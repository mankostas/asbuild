<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Branding;
use Illuminate\Http\Request;

class BrandingController extends Controller
{
    public function show(Request $request)
    {
        $tenantId = app()->has('tenant_id') ? app('tenant_id') : null;
        $branding = Branding::where('tenant_id', $tenantId)->first();
        if (! $branding) {
            $branding = Branding::whereNull('tenant_id')->first();
        }
        return response()->json($branding ?? []);
    }

    public function update(Request $request)
    {
        $data = $request->validate([
            'name' => 'nullable|string',
            'color' => 'nullable|string',
            'secondary_color' => 'nullable|string',
            'logo' => 'nullable|string',
            'logo_dark' => 'nullable|string',
            'email_from' => 'nullable|email',
            'footer_left' => 'nullable|string',
            'footer_right' => 'nullable|string',
        ]);

        $tenantId = $request->user()->hasRole('SuperAdmin') ? null : app('tenant_id');

        $branding = Branding::updateOrCreate(['tenant_id' => $tenantId], $data);

        return response()->json($branding);
    }
}
