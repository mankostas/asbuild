<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;

// Web routes are handled by the SPA; see RouteServiceProvider fallback.

Route::prefix('auth')->middleware('api')->group(function () {
    Route::post('login', [AuthController::class, 'login'])
        ->middleware('throttle:auth')
        ->name('login');
});
