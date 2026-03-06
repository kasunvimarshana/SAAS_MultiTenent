<?php

return [
    'product_service' => [
        'url' => env('PRODUCT_SERVICE_URL', 'http://product-service:8002'),
    ],
    'order_service' => [
        'url' => env('ORDER_SERVICE_URL', 'http://order-service:8004'),
    ],
];
