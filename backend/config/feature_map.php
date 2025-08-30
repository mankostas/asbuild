<?php

return [
    'tasks' => [
        'label' => 'Tasks',
        'abilities' => [
            'tasks.view',
            'tasks.create',
            'tasks.update',
            'tasks.delete',
            'tasks.assign',
            'tasks.status.update',
            'tasks.comment.create',
            'tasks.attach.upload',
            'tasks.board.view',
            'tasks.list.view',
            'tasks.watch',
            'tasks.manage',
        ],
    ],
    'notifications' => [
        'label' => 'Notifications',
        'abilities' => [
            'notifications.view',
            'notifications.manage',
        ],
    ],
    'reports' => [
        'label' => 'Reports',
        'abilities' => [
            'reports.view',
            'reports.manage',
        ],
    ],
    'roles' => [
        'label' => 'Roles & Permissions',
        'abilities' => [
            'roles.view',
            'roles.manage',
        ],
    ],
    'task_types' => [
        'label' => 'Task Types',
        'abilities' => [
            'task_types.manage',
            'task_type_versions.manage',
            'task_automations.manage',
        ],
    ],
    'teams' => [
        'label' => 'Teams',
        'abilities' => [
            'teams.view',
            'teams.create',
            'teams.update',
            'teams.delete',
            'teams.manage',
        ],
    ],
    'task_statuses' => [
        'label' => 'Task Statuses',
        'abilities' => [
            'task_statuses.manage',
        ],
    ],
    'employees' => [
        'label' => 'Employees',
        'abilities' => [
            'employees.view',
            'employees.create',
            'employees.update',
            'employees.delete',
            'employees.manage',
        ],
    ],
    'themes' => [
        'label' => 'Theme Customizer',
        'abilities' => [
            'themes.view',
            'themes.manage',
        ],
    ],
    'tenants' => [
        'label' => 'Tenants',
        'abilities' => [
            'tenants.view',
            'tenants.create',
            'tenants.update',
            'tenants.delete',
            'tenants.manage',
        ],
    ],
    'branding' => [
        'label' => 'Branding & Footer',
        'abilities' => [
            'branding.manage',
        ],
    ],
    'gdpr' => [
        'label' => 'GDPR',
        'abilities' => [
            'gdpr.view',
            'gdpr.manage',
            'gdpr.export',
            'gdpr.delete',
        ],
    ],
];
