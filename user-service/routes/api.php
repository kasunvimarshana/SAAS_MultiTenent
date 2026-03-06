<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\HealthCheckController;
use App\Http\Controllers\WebhookController;
use Illuminate\Support\Facades\Route;

// Health check (no auth required)
Route::get('/health', [HealthCheckController::class, 'check']);

// Webhook endpoint
Route::post('/webhooks/receive', [WebhookController::class, 'handle']);

// Public auth routes
Route::prefix('auth')->group(function () {
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/login', [AuthController::class, 'login']);
});

// Protected routes
Route::middleware(['auth:api'])->group(function () {
    // Auth routes
    Route::prefix('auth')->group(function () {
        Route::post('/logout', [AuthController::class, 'logout']);
        Route::get('/me', [AuthController::class, 'me']);
        Route::post('/refresh', [AuthController::class, 'refreshToken']);
    });

    // Tenant-scoped routes
    Route::middleware(['tenant'])->group(function () {
        Route::apiResource('users', UserController::class)->except(['store']);
        Route::post('/users/{id}/roles', [UserController::class, 'assignRole']);
    });
});
