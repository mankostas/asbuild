<?php

return [
    'dashboard' => [
        'label' => 'Dashboard',
        'abilities' => [
            'dashboard.view',
        ],
    ],
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
            'tasks.export',
            'tasks.watch',
            'tasks.manage',
        ],
    ],
    'manuals' => [
        'label' => 'Manuals',
        'abilities' => [
            'manuals.manage',
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
    'billing' => [
        'label' => 'Billing',
        'abilities' => [
            'billing.view',
            'billing.manage',
        ],
    ],
    'roles' => [
        'label' => 'Roles & Permissions',
        'abilities' => [
            'roles.view',
            'roles.create',
            'roles.update',
            'roles.delete',
            'roles.manage',
        ],
    ],
    'task_types' => [
        'label' => 'Task Types',
        'abilities' => [
            'task_types.view',
            'task_types.create',
            'task_types.update',
            'task_types.delete',
            'task_types.manage',
            'task_sla_policies.manage',
            'task_automations.manage',
            'task_field_snippets.manage',
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
            'task_statuses.view',
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
