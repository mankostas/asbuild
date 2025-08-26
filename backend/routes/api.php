<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\FileController;
use App\Http\Controllers\Api\UploadController;
use App\Http\Controllers\Api\AppointmentController;
use App\Http\Controllers\Api\AppointmentCommentController;
use App\Http\Controllers\Api\AppointmentTypeController;
use App\Http\Controllers\Api\ManualController;
use App\Http\Controllers\Api\NotificationController;
use App\Http\Controllers\Api\ReportController;
use App\Http\Controllers\Api\SettingsController;
use App\Http\Controllers\Api\EmployeeController;
use App\Http\Controllers\Api\TenantController;
use App\Http\Controllers\Api\GdprController;

Route::middleware(['api','tenant'])->get('/health', function () {
    return response()->json(['status' => 'ok', 'tenant' => config('tenant.branding')]);
});

Route::prefix('auth')->group(function () {
    Route::post('login', [AuthController::class, 'login'])
        ->middleware('throttle:auth')
        ->name('login');
    Route::post('logout', [AuthController::class, 'logout'])->middleware('auth:sanctum');
    Route::post('refresh', [AuthController::class, 'refresh']);
    Route::post('password/email', [AuthController::class, 'sendResetLinkEmail']);
    Route::post('password/reset', [AuthController::class, 'reset']);
});

Route::middleware('auth:sanctum')->get('/me', function (Request $request) {
    return $request->user()->load('roles');
});

Route::get('files/{file}/{variant?}', [FileController::class, 'download'])
    ->name('files.download')
    ->middleware('signed.url');

Route::prefix('uploads')->middleware(['auth:sanctum', 'tenant'])->group(function () {
    Route::post('chunk', [UploadController::class, 'chunk'])->middleware('throttle:uploads');
    Route::delete('cleanup', [UploadController::class, 'cleanup'])->middleware('throttle:uploads');
});

Route::middleware(['auth:sanctum'])->group(function () {
    Route::apiResource('tenants', TenantController::class);
    Route::post('tenants/{tenant}/impersonate', [TenantController::class, 'impersonate']);
});

Route::middleware(['auth:sanctum', 'tenant'])->group(function () {
    Route::apiResource('appointments', AppointmentController::class);
    Route::apiResource('appointment-types', AppointmentTypeController::class);
    Route::apiResource('appointments.comments', AppointmentCommentController::class)
        ->shallow()
        ->only(['index', 'store', 'show', 'update', 'destroy']);
    Route::get('manuals/{manual}/download', [ManualController::class, 'download']);
    Route::post('manuals/{manual}/replace', [ManualController::class, 'replace']);
    Route::apiResource('manuals', ManualController::class);
    Route::get('notifications', [NotificationController::class, 'index']);
    Route::post('notifications/{notification}/read', [NotificationController::class, 'markAsRead']);
    Route::get('notification-preferences', [NotificationController::class, 'getPreferences']);
    Route::put('notification-preferences', [NotificationController::class, 'updatePreferences']);

    Route::apiResource('employees', EmployeeController::class);
    Route::post('employees/{employee}', [EmployeeController::class, 'update']);

    Route::get('settings/branding', [SettingsController::class, 'getBranding']);
    Route::put('settings/branding', [SettingsController::class, 'updateBranding']);
    Route::put('settings/profile', [SettingsController::class, 'updateProfile']);

    Route::prefix('gdpr')->group(function () {
        Route::get('export', [GdprController::class, 'export']);
        Route::get('consents', [GdprController::class, 'consents']);
        Route::put('consents', [GdprController::class, 'updateConsents']);
        Route::post('delete', [GdprController::class, 'requestDelete']);
    });

    Route::prefix('reports')->group(function () {
        Route::get('overview', [ReportController::class, 'overview']);
        Route::get('kpis', [ReportController::class, 'kpis']);
        Route::get('materials', [ReportController::class, 'materials']);
        Route::get('export', [ReportController::class, 'export']);
    });
});
