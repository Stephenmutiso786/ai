<?php

return [
    'super_admin' => [
        'label' => 'Super Admin',
        'permissions' => ['*'],
    ],
    'admin' => [
        'label' => 'Admin',
        'permissions' => [
            'view-admin-dashboard',
            'manage-users',
            'manage-plans',
            'manage-custom-requests',
            'manage-settings',
            'manage-ai-lab',
            'manage-operations',
            'manage-broker-certification',
            'manage-trading-workspace',
        ],
    ],
    'hod' => [
        'label' => 'HOD',
        'permissions' => [
            'view-admin-dashboard',
            'manage-trading-workspace',
            'manage-broker-certification',
        ],
    ],
    'analyst' => [
        'label' => 'Analyst',
        'permissions' => [
            'view-admin-dashboard',
            'manage-ai-lab',
            'manage-trading-workspace',
        ],
    ],
    'support' => [
        'label' => 'Support',
        'permissions' => [
            'view-admin-dashboard',
            'manage-custom-requests',
            'manage-broker-certification',
        ],
    ],
    'finance' => [
        'label' => 'Finance',
        'permissions' => [
            'view-admin-dashboard',
            'manage-plans',
            'manage-custom-requests',
            'manage-users',
        ],
    ],
    'client' => [
        'label' => 'Client',
        'permissions' => [
            'manage-trading-workspace',
        ],
    ],
];
