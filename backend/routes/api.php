<?php

use Illuminate\Support\Facades\Route;

Route::middleware(['api','tenant'])->get('/health', function () {
    return response()->json(['status' => 'ok', 'tenant' => config('tenant.branding')]);
});
