<?php

return [
    'model' => \App\Models\Tenant::class,
    'header' => 'X-Tenant-ID',
    'default_plan' => 'basic',
    'plans' => [
        'basic' => [
            'max_users' => 10,
            'max_products' => 100,
            'max_orders' => 500,
            'features' => ['inventory', 'orders'],
        ],
        'professional' => [
            'max_users' => 50,
            'max_products' => 1000,
            'max_orders' => 5000,
            'features' => ['inventory', 'orders', 'webhooks', 'advanced_reports'],
        ],
        'enterprise' => [
            'max_users' => -1, // unlimited
            'max_products' => -1,
            'max_orders' => -1,
            'features' => ['inventory', 'orders', 'webhooks', 'advanced_reports', 'custom_integrations', 'sso'],
        ],
    ],
];
