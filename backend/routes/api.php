<?php

use Illuminate\Support\Facades\Route;
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
use App\Http\Controllers\Api\RoleController;
use App\Http\Controllers\Api\StatusController;
use App\Http\Controllers\Api\TeamController;
use App\Http\Controllers\Api\LookupController;
use App\Http\Controllers\Api\CalendarController;
use App\Http\Controllers\Api\BrandingController;
use App\Http\Middleware\EnsureTenantScope;
use App\Http\Middleware\Ability;
use Illuminate\Http\Request;

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
    Route::get('password/reset/{token}', function (Request $request, string $token) {
        $frontendUrl = explode(',', env('FRONTEND_URL', 'http://localhost:5173'))[0];
        $frontendUrl = rtrim($frontendUrl, '/');
        $query = http_build_query([
            'token' => $token,
            'email' => $request->email,
        ]);
        return redirect("{$frontendUrl}/reset-password?{$query}");
    })->name('password.reset');
});

Route::middleware('auth:sanctum')->get('/me', [AuthController::class, 'me']);

Route::get('files/{file}/{variant?}', [FileController::class, 'download'])
    ->name('files.download')
    ->middleware('signed.url');

Route::prefix('uploads')->middleware(['auth:sanctum', EnsureTenantScope::class])->group(function () {
    Route::post('chunk', [UploadController::class, 'chunk'])->middleware('throttle:uploads');
    Route::post('{uploadId}/finalize', [UploadController::class, 'finalize'])->middleware('throttle:uploads');
    Route::delete('cleanup', [UploadController::class, 'cleanup'])->middleware('throttle:uploads');
});

Route::middleware(['auth:sanctum'])->group(function () {
    Route::apiResource('tenants', TenantController::class)->middleware([
        'index' => Ability::class . ':tenants.view',
        'show' => Ability::class . ':tenants.view',
        'store' => Ability::class . ':tenants.create',
        'update' => Ability::class . ':tenants.update',
        'destroy' => Ability::class . ':tenants.delete',
    ]);
    Route::post('tenants/{tenant}/impersonate', [TenantController::class, 'impersonate'])
        ->middleware(Ability::class . ':tenants.manage');
});

Route::middleware(['auth:sanctum', EnsureTenantScope::class])->group(function () {
    Route::apiResource('appointments', AppointmentController::class)->middleware([
        'index' => Ability::class . ':appointments.view',
        'show' => Ability::class . ':appointments.view',
        'store' => Ability::class . ':appointments.create',
        'update' => Ability::class . ':appointments.update',
        'destroy' => Ability::class . ':appointments.delete',
    ]);
    Route::post('appointments/{appointment}/files', [FileController::class, 'attachToAppointment'])
        ->middleware(Ability::class . ':appointments.update');

    Route::apiResource('appointment-types', AppointmentTypeController::class)
        ->only(['index', 'show'])
        ->middleware(Ability::class . ':types.view');
    Route::apiResource('roles', RoleController::class)
        ->only(['index', 'show'])
        ->middleware(Ability::class . ':roles.view');
    Route::apiResource('statuses', StatusController::class)
        ->only(['index', 'show'])
        ->middleware(Ability::class . ':statuses.view');
    Route::get('statuses/{status}/transitions', [StatusController::class, 'transitions'])
        ->middleware(Ability::class . ':statuses.view');
    Route::apiResource('teams', TeamController::class)
        ->only(['index', 'show'])
        ->middleware(Ability::class . ':teams.view');

    Route::post('appointment-types', [AppointmentTypeController::class, 'store'])
        ->middleware(Ability::class . ':types.create')
        ->name('appointment-types.store');
    Route::match(['put', 'patch'], 'appointment-types/{appointment_type}', [AppointmentTypeController::class, 'update'])
        ->middleware(Ability::class . ':types.update')
        ->name('appointment-types.update');
    Route::delete('appointment-types/{appointment_type}', [AppointmentTypeController::class, 'destroy'])
        ->middleware(Ability::class . ':types.delete')
        ->name('appointment-types.destroy');
    Route::post('appointment-types/{appointment_type}/copy-to-tenant', [AppointmentTypeController::class, 'copyToTenant'])
        ->middleware(Ability::class . ':types.create')
        ->name('appointment-types.copy');

    Route::post('roles', [RoleController::class, 'store'])
        ->middleware(Ability::class . ':roles.manage')
        ->name('roles.store');
    Route::match(['put', 'patch'], 'roles/{role}', [RoleController::class, 'update'])
        ->middleware(Ability::class . ':roles.manage')
        ->name('roles.update');
    Route::delete('roles/{role}', [RoleController::class, 'destroy'])
        ->middleware(Ability::class . ':roles.manage')
        ->name('roles.destroy');
    Route::post('roles/{role}/assign', [RoleController::class, 'assign'])
        ->middleware(Ability::class . ':roles.manage')
        ->name('roles.assign');

    Route::post('teams', [TeamController::class, 'store'])
        ->middleware(Ability::class . ':teams.create')
        ->name('teams.store');
    Route::match(['put', 'patch'], 'teams/{team}', [TeamController::class, 'update'])
        ->middleware(Ability::class . ':teams.update')
        ->name('teams.update');
    Route::delete('teams/{team}', [TeamController::class, 'destroy'])
        ->middleware(Ability::class . ':teams.delete')
        ->name('teams.destroy');
    Route::post('teams/{team}/employees', [TeamController::class, 'syncEmployees'])
        ->middleware(Ability::class . ':teams.update');

    Route::post('statuses', [StatusController::class, 'store'])
        ->middleware(Ability::class . ':statuses.create')
        ->name('statuses.store');
    Route::match(['put', 'patch'], 'statuses/{status}', [StatusController::class, 'update'])
        ->middleware(Ability::class . ':statuses.update')
        ->name('statuses.update');
    Route::delete('statuses/{status}', [StatusController::class, 'destroy'])
        ->middleware(Ability::class . ':statuses.delete')
        ->name('statuses.destroy');
    Route::post('statuses/{status}/copy-to-tenant', [StatusController::class, 'copyToTenant'])
        ->middleware(Ability::class . ':statuses.create')
        ->name('statuses.copy');
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

    Route::apiResource('employees', EmployeeController::class)->middleware([
        'index' => Ability::class . ':employees.view',
        'show' => Ability::class . ':employees.view',
        'store' => Ability::class . ':employees.manage',
        'update' => Ability::class . ':employees.manage',
        'destroy' => Ability::class . ':employees.manage',
    ]);
    Route::post('employees/{employee}', [EmployeeController::class, 'update'])
        ->middleware(Ability::class . ':employees.manage');

    Route::get('branding', [BrandingController::class, 'show']);
    Route::put('branding', [BrandingController::class, 'update'])
        ->middleware(Ability::class . ':branding.manage');
    Route::put('settings/profile', [SettingsController::class, 'updateProfile']);
    Route::get('settings/theme', [SettingsController::class, 'getTheme'])
        ->middleware(Ability::class . ':themes.view');
    Route::put('settings/theme', [SettingsController::class, 'updateTheme'])
        ->middleware(Ability::class . ':themes.manage');

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

    // Lookup endpoints
    Route::get('lookups/assignees', [LookupController::class, 'assignees']);
    Route::get('lookups/abilities', [LookupController::class, 'abilities']); // ?forTenant=1
    Route::get('lookups/features', [LookupController::class, 'features']);
    Route::get('calendar/events', [CalendarController::class, 'events']);
});
