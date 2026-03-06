<?php

use App\Http\Controllers\OrderController;
use App\Http\Controllers\HealthCheckController;
use Illuminate\Support\Facades\Route;

Route::get('/health', [HealthCheckController::class, 'check']);

Route::middleware(['auth:api', 'tenant'])->group(function () {
    Route::apiResource('orders', OrderController::class)->except(['update', 'destroy']);
    Route::post('/orders/{id}/cancel', [OrderController::class, 'cancel']);
});
