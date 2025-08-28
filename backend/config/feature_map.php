<?php

return [
    'appointments' => [
        'label' => 'Appointments',
        'abilities' => [
            'appointments.view',
            'appointments.update',
            'appointments.manage',
            'appointments.assign',
        ],
    ],
    'roles' => [
        'label' => 'Roles & Permissions',
        'abilities' => ['roles.manage'],
    ],
    'types' => [
        'label' => 'Appointment Types',
        'abilities' => ['types.manage'],
    ],
    'teams' => [
        'label' => 'Teams',
        'abilities' => ['teams.manage'],
    ],
    'statuses' => [
        'label' => 'Statuses',
        'abilities' => ['statuses.manage'],
    ],
    // Additional features can be listed here as needed, e.g. 'reports', 'billing', 'employees', …
];
