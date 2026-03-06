<?php

return [
    'driver' => env('MESSAGE_BROKER_DRIVER', 'rabbitmq'),

    'rabbitmq' => [
        'host' => env('RABBITMQ_HOST', 'rabbitmq'),
        'port' => (int) env('RABBITMQ_PORT', 5672),
        'user' => env('RABBITMQ_USER', 'guest'),
        'password' => env('RABBITMQ_PASSWORD', 'guest'),
        'vhost' => env('RABBITMQ_VHOST', '/'),
    ],

    'kafka' => [
        'brokers' => env('KAFKA_BROKERS', 'kafka:9092'),
        'group_id' => env('KAFKA_GROUP_ID', 'saas-inventory'),
    ],

    'topics' => [
        'user_created' => 'user.created',
        'user_deleted' => 'user.deleted',
        'product_created' => 'product.created',
        'product_deleted' => 'product.deleted',
        'inventory_updated' => 'inventory.updated',
        'order_created' => 'order.created',
        'order_saga' => 'order.saga',
    ],
];
