<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\FileController;
use App\Http\Controllers\Api\UploadController;
use App\Http\Controllers\Api\AppointmentController;
use App\Http\Controllers\Api\AppointmentCommentController;
use App\Http\Controllers\Api\AppointmentTypeController;

Route::middleware(['api','tenant'])->get('/health', function () {
    return response()->json(['status' => 'ok', 'tenant' => config('tenant.branding')]);
});

Route::prefix('auth')->group(function () {
    Route::post('login', [AuthController::class, 'login'])->middleware('throttle:6,1');
    Route::post('logout', [AuthController::class, 'logout'])->middleware('auth:sanctum');
    Route::post('refresh', [AuthController::class, 'refresh']);
    Route::post('password/email', [AuthController::class, 'sendResetLinkEmail']);
    Route::post('password/reset', [AuthController::class, 'reset']);
});

Route::get('files/{file}/{variant?}', [FileController::class, 'download'])
    ->name('files.download')
    ->middleware('signed');

Route::prefix('uploads')->group(function () {
    Route::post('chunk', [UploadController::class, 'chunk']);
    Route::delete('cleanup', [UploadController::class, 'cleanup']);
});

Route::middleware(['auth:sanctum', 'tenant'])->group(function () {
    Route::apiResource('appointments', AppointmentController::class);
    Route::apiResource('appointment-types', AppointmentTypeController::class);
    Route::apiResource('appointments.comments', AppointmentCommentController::class)
        ->shallow()
        ->only(['index', 'store', 'destroy']);
});
