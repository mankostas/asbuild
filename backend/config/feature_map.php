<?php

return [
    'appointments' => [
        'label' => 'Appointments',
        'abilities' => [
            'appointments.view',
            'appointments.create',
            'appointments.update',
            'appointments.delete',
            'appointments.assign',
            'appointments.manage',
        ],
    ],
    'roles' => [
        'label' => 'Roles & Permissions',
        'abilities' => [
            'roles.view',
            'roles.manage',
        ],
    ],
    'types' => [
        'label' => 'Appointment Types',
        'abilities' => [
            'types.view',
            'types.create',
            'types.update',
            'types.delete',
            'types.manage',
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
    'statuses' => [
        'label' => 'Statuses',
        'abilities' => [
            'statuses.view',
            'statuses.create',
            'statuses.update',
            'statuses.delete',
            'statuses.manage',
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
    // Additional features can be listed here as needed, e.g. 'reports', 'billing', 'employees', …
];
