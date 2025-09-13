<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\FileController;
use App\Http\Controllers\Api\UploadController;
use App\Http\Controllers\Api\TaskController;
use App\Http\Controllers\Api\TaskCommentController;
use App\Http\Controllers\Api\TaskWatcherController;
use App\Http\Controllers\Api\TaskTypeController;
use App\Http\Controllers\Api\TaskBoardController;
use App\Http\Controllers\Api\ManualController;
use App\Http\Controllers\Api\NotificationController;
use App\Http\Controllers\Api\ReportController;
use App\Http\Controllers\Api\SettingsController;
use App\Http\Controllers\Api\EmployeeController;
use App\Http\Controllers\Api\TenantController;
use App\Http\Controllers\Api\GdprController;
use App\Http\Controllers\Api\RoleController;
use App\Http\Controllers\Api\TaskStatusController;
use App\Http\Controllers\Api\TeamController;
use App\Http\Controllers\Api\LookupController;
use App\Http\Controllers\Api\CalendarController;
use App\Http\Controllers\Api\BrandingController;
use App\Http\Controllers\Api\TaskSubtaskController;
use App\Http\Controllers\Api\TaskSlaPolicyController;
use App\Http\Controllers\Api\TaskAutomationController;
use App\Http\Middleware\EnsureTenantScope;
use App\Http\Middleware\Ability;
use Illuminate\Http\Request;

Route::middleware(['api','tenant'])->get('/health', function () {
    return response()->json(['status' => 'ok', 'tenant' => config('tenant.branding')]);
});

Route::middleware(['api','tenant'])->get('branding', [BrandingController::class, 'show']);

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
    Route::post('{uploadId}/finalize', [UploadController::class, 'finalize'])
        ->middleware(['throttle:uploads', Ability::class . ':tasks.attach.upload']);
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
    Route::apiResource('tasks', TaskController::class)->middleware([
        'index' => Ability::class . ':tasks.view',
        'show' => Ability::class . ':tasks.view',
        'store' => Ability::class . ':tasks.create',
        'update' => Ability::class . ':tasks.update',
        'destroy' => Ability::class . ':tasks.delete',
    ]);
    Route::post('tasks/{task}/status', [TaskController::class, 'updateStatus'])
        ->middleware(Ability::class . ':tasks.status.update');
    Route::post('tasks/{task}/files', [FileController::class, 'attachToTask'])
        ->middleware(Ability::class . ':tasks.attach.upload');
    Route::post('tasks/{task}/watch', [TaskWatcherController::class, 'store'])
        ->middleware(Ability::class . ':tasks.watch');
    Route::delete('tasks/{task}/watch', [TaskWatcherController::class, 'destroy'])
        ->middleware(Ability::class . ':tasks.watch');
    Route::post('tasks/{task}/subtasks', [TaskSubtaskController::class, 'store'])
        ->middleware(Ability::class . ':tasks.update');
    Route::patch('tasks/{task}/subtasks/reorder', [TaskSubtaskController::class, 'reorder'])
        ->middleware(Ability::class . ':tasks.update');
    Route::patch('tasks/{task}/subtasks/{subtask}', [TaskSubtaskController::class, 'update'])
        ->middleware(Ability::class . ':tasks.update')
        ->whereNumber('subtask');
    Route::delete('tasks/{task}/subtasks/{subtask}', [TaskSubtaskController::class, 'destroy'])
        ->middleware(Ability::class . ':tasks.update')
        ->whereNumber('subtask');

    Route::get('task-board', [TaskBoardController::class, 'index'])
        ->middleware(Ability::class . ':tasks.view');
    Route::get('task-board/column', [TaskBoardController::class, 'column'])
        ->middleware(Ability::class . ':tasks.view');
    Route::patch('task-board/move', [TaskBoardController::class, 'move'])
        ->middleware(Ability::class . ':tasks.update');

    Route::get('task-types', [TaskTypeController::class, 'index'])
        ->middleware('ability:task_types.view');
    Route::get('task-types/{task_type}', [TaskTypeController::class, 'show'])
        ->middleware('ability:task_types.view')
        ->whereNumber('task_type');
    Route::post('task-types', [TaskTypeController::class, 'store'])
        ->middleware('ability:task_types.create')
        ->name('task-types.store');
    Route::match(['put', 'patch'], 'task-types/{task_type}', [TaskTypeController::class, 'update'])
        ->middleware('ability:task_types.update')
        ->name('task-types.update')
        ->whereNumber('task_type');
    Route::delete('task-types/{task_type}', [TaskTypeController::class, 'destroy'])
        ->middleware('ability:task_types.delete')
        ->name('task-types.destroy')
        ->whereNumber('task_type');
    Route::get('task-types/options', [TaskTypeController::class, 'options'])
        ->middleware('ability:tasks.create|task_types.view');

    Route::get('task-types/{task_type}/sla-policies', [TaskSlaPolicyController::class, 'index'])
        ->middleware(Ability::class . ':task_sla_policies.manage')
        ->whereNumber('task_type');
    Route::post('task-types/{task_type}/sla-policies', [TaskSlaPolicyController::class, 'store'])
        ->middleware(Ability::class . ':task_sla_policies.manage')
        ->whereNumber('task_type');
    Route::put('task-types/{task_type}/sla-policies/{task_sla_policy}', [TaskSlaPolicyController::class, 'update'])
        ->middleware(Ability::class . ':task_sla_policies.manage')
        ->whereNumber('task_type')
        ->whereNumber('task_sla_policy');
    Route::delete('task-types/{task_type}/sla-policies/{task_sla_policy}', [TaskSlaPolicyController::class, 'destroy'])
        ->middleware(Ability::class . ':task_sla_policies.manage')
        ->whereNumber('task_type')
        ->whereNumber('task_sla_policy');

    Route::get('task-types/{task_type}/automations', [TaskAutomationController::class, 'index'])
        ->middleware(Ability::class . ':task_automations.manage')
        ->whereNumber('task_type');
    Route::post('task-types/{task_type}/automations', [TaskAutomationController::class, 'store'])
        ->middleware(Ability::class . ':task_automations.manage')
        ->whereNumber('task_type');
    Route::put('task-types/{task_type}/automations/{task_automation}', [TaskAutomationController::class, 'update'])
        ->middleware(Ability::class . ':task_automations.manage')
        ->whereNumber('task_type')
        ->whereNumber('task_automation');
    Route::delete('task-types/{task_type}/automations/{task_automation}', [TaskAutomationController::class, 'destroy'])
        ->middleware(Ability::class . ':task_automations.manage')
        ->whereNumber('task_type')
        ->whereNumber('task_automation');
    Route::apiResource('roles', RoleController::class)
        ->only(['index', 'show'])
        ->middleware(Ability::class . ':roles.view');
    Route::apiResource('task-statuses', TaskStatusController::class)
        ->only(['index', 'show'])
        ->middleware(Ability::class . ':task_statuses.manage');
    Route::get('task-statuses/{task_status}/transitions', [TaskStatusController::class, 'transitions'])
        ->middleware(Ability::class . ':task_statuses.manage');
    Route::apiResource('teams', TeamController::class)
        ->only(['index', 'show'])
        ->middleware(Ability::class . ':teams.view');

    Route::post('task-types/{task_type}/copy-to-tenant', [TaskTypeController::class, 'copyToTenant'])
        ->middleware('ability:task_types.manage')
        ->name('task-types.copy')
        ->whereNumber('task_type');

    Route::post('task-types/bulk-copy-to-tenant', [TaskTypeController::class, 'bulkCopyToTenant'])
        ->middleware('ability:task_types.manage')
        ->name('task-types.bulk-copy');

    Route::post('task-types/bulk-delete', [TaskTypeController::class, 'bulkDestroy'])
        ->middleware('ability:task_types.delete')
        ->name('task-types.bulk-destroy');

    Route::post('task-types/validate', [TaskTypeController::class, 'validateSchema'])
        ->middleware('ability:task_types.create')
        ->name('task-types.validate-schema');

    Route::post('task-types/{task_type}/validate', [TaskTypeController::class, 'previewValidate'])
        ->middleware('ability:task_types.manage')
        ->name('task-types.validate')
        ->whereNumber('task_type');

    Route::post('task-types/{task_type}/export', [TaskTypeController::class, 'export'])
        ->middleware('ability:task_types.manage')
        ->name('task-types.export')
        ->whereNumber('task_type');

    Route::post('task-types/import', [TaskTypeController::class, 'import'])
        ->middleware('ability:task_types.manage')
        ->name('task-types.import');

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

    Route::post('task-statuses', [TaskStatusController::class, 'store'])
        ->middleware(Ability::class . ':task_statuses.manage')
        ->name('task-statuses.store');
    Route::match(['put', 'patch'], 'task-statuses/{task_status}', [TaskStatusController::class, 'update'])
        ->middleware(Ability::class . ':task_statuses.manage')
        ->name('task-statuses.update');
    Route::delete('task-statuses/{task_status}', [TaskStatusController::class, 'destroy'])
        ->middleware(Ability::class . ':task_statuses.manage')
        ->name('task-statuses.destroy');
    Route::post('task-statuses/{task_status}/copy-to-tenant', [TaskStatusController::class, 'copyToTenant'])
        ->middleware(Ability::class . ':task_statuses.manage')
        ->name('task-statuses.copy');
    Route::apiResource('tasks.comments', TaskCommentController::class)
        ->shallow()
        ->only(['index', 'store', 'show', 'update', 'destroy'])
        ->middleware([
            'index' => Ability::class . ':tasks.view',
            'show' => Ability::class . ':tasks.view',
            'store' => Ability::class . ':tasks.comment.create',
            'update' => Ability::class . ':tasks.update',
            'destroy' => Ability::class . ':tasks.delete',
        ]);
    Route::get('manuals/{manual}/download', [ManualController::class, 'download']);
    Route::post('manuals/{manual}/replace', [ManualController::class, 'replace']);
    Route::apiResource('manuals', ManualController::class);
    Route::get('notifications', [NotificationController::class, 'index'])
        ->middleware(Ability::class . ':notifications.view');
    Route::post('notifications/{notification}/read', [NotificationController::class, 'markAsRead'])
        ->middleware(Ability::class . ':notifications.manage');
    Route::get('notification-preferences', [NotificationController::class, 'getPreferences'])
        ->middleware(Ability::class . ':notifications.view');
    Route::put('notification-preferences', [NotificationController::class, 'updatePreferences'])
        ->middleware(Ability::class . ':notifications.manage');

    Route::apiResource('employees', EmployeeController::class)->middleware([
        'index' => Ability::class . ':employees.view',
        'show' => Ability::class . ':employees.view',
        'store' => Ability::class . ':employees.manage',
        'update' => Ability::class . ':employees.manage',
        'destroy' => Ability::class . ':employees.manage',
    ]);
    Route::post('employees/{employee}', [EmployeeController::class, 'update'])
        ->middleware(Ability::class . ':employees.manage');
    Route::patch('employees/{employee}/toggle-status', [EmployeeController::class, 'toggleStatus'])
        ->middleware(Ability::class . ':employees.manage');
    Route::post('employees/{employee}/impersonate', [EmployeeController::class, 'impersonate'])
        ->middleware(Ability::class . ':employees.manage');
    Route::post('employees/{employee}/resend-invite', [EmployeeController::class, 'resendInvite'])
        ->middleware(Ability::class . ':employees.manage');

    Route::put('branding', [BrandingController::class, 'update'])
        ->middleware(Ability::class . ':branding.manage');
    Route::put('settings/profile', [SettingsController::class, 'updateProfile']);
    Route::get('settings/theme', [SettingsController::class, 'getTheme'])
        ->middleware(Ability::class . ':themes.view');
    Route::put('settings/theme', [SettingsController::class, 'updateTheme'])
        ->middleware(Ability::class . ':themes.manage');

    Route::prefix('gdpr')->group(function () {
        Route::get('export', [GdprController::class, 'export'])
            ->middleware(Ability::class . ':gdpr.export');
        Route::get('consents', [GdprController::class, 'consents'])
            ->middleware(Ability::class . ':gdpr.view');
        Route::put('consents', [GdprController::class, 'updateConsents'])
            ->middleware(Ability::class . ':gdpr.manage');
        Route::post('delete', [GdprController::class, 'requestDelete'])
            ->middleware(Ability::class . ':gdpr.delete');
    });

    Route::prefix('reports')
        ->middleware(Ability::class . ':reports.view')
        ->group(function () {
            Route::get('overview', [ReportController::class, 'overview']);
            Route::get('kpis', [ReportController::class, 'kpis']);
            Route::get('materials', [ReportController::class, 'materials']);
            Route::get('tasks/overview', [ReportController::class, 'tasksOverview']);
            Route::get('export', [ReportController::class, 'export'])
                ->middleware(Ability::class . ':reports.manage');
        });

    // Lookup endpoints
    Route::get('lookups/assignees', [LookupController::class, 'assignees']);
    Route::get('lookups/abilities', [LookupController::class, 'abilities']); // ?forTenant=1
    Route::get('lookups/features', [LookupController::class, 'features']);
    Route::get('calendar/events', [CalendarController::class, 'events']);
});
