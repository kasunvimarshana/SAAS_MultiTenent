<?php

use App\Http\Controllers\InventoryController;
use App\Http\Controllers\HealthCheckController;
use Illuminate\Support\Facades\Route;

Route::get('/health', [HealthCheckController::class, 'check']);

Route::middleware(['auth:api', 'tenant'])->group(function () {
    Route::apiResource('inventory', InventoryController::class);
    Route::post('/inventory/{id}/add-stock', [InventoryController::class, 'addStock']);
    Route::post('/inventory/{id}/remove-stock', [InventoryController::class, 'removeStock']);
    Route::get('/inventory/reports/low-stock', [InventoryController::class, 'lowStock']);
    Route::get('/inventory/with-products', [InventoryController::class, 'indexWithProducts']);
    Route::get('/inventory/search/product-name', [InventoryController::class, 'searchByProductName']);
});
