<?php

return [
    'inventory_service' => ['url' => env('INVENTORY_SERVICE_URL', 'http://inventory-service:8003')],
    'product_service' => ['url' => env('PRODUCT_SERVICE_URL', 'http://product-service:8002')],
    'user_service' => ['url' => env('USER_SERVICE_URL', 'http://user-service:8001')],
];
