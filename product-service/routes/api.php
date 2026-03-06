<?php

use App\Http\Controllers\ProductController;
use App\Http\Controllers\HealthCheckController;
use Illuminate\Support\Facades\Route;

Route::get('/health', [HealthCheckController::class, 'check']);

Route::middleware(['auth:api', 'tenant'])->group(function () {
    Route::apiResource('products', ProductController::class);
    Route::get('/products/search/query', [ProductController::class, 'search']);
});
