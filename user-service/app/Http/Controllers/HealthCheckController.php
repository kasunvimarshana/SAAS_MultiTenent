<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class HealthCheckController extends BaseController
{
    public function check(): JsonResponse
    {
        $checks = [];
        $healthy = true;

        // Database check
        try {
            DB::connection()->getPdo();
            $checks['database'] = ['status' => 'healthy', 'message' => 'Database connection OK'];
        } catch (\Throwable $e) {
            $checks['database'] = ['status' => 'unhealthy', 'message' => $e->getMessage()];
            $healthy = false;
        }

        // Cache/Redis check
        try {
            Cache::put('health_check', 'ok', 10);
            $value = Cache::get('health_check');
            $checks['cache'] = [
                'status' => $value === 'ok' ? 'healthy' : 'unhealthy',
                'message' => $value === 'ok' ? 'Cache connection OK' : 'Cache read/write failed',
            ];
            if ($value !== 'ok') {
                $healthy = false;
            }
        } catch (\Throwable $e) {
            $checks['cache'] = ['status' => 'unhealthy', 'message' => $e->getMessage()];
            $healthy = false;
        }

        // Application check
        $checks['app'] = [
            'status' => 'healthy',
            'name' => config('app.name'),
            'env' => config('app.env'),
            'version' => '1.0.0',
        ];

        $statusCode = $healthy ? 200 : 503;
        return response()->json([
            'status' => $healthy ? 'healthy' : 'unhealthy',
            'service' => 'user-service',
            'timestamp' => now()->toISOString(),
            'checks' => $checks,
        ], $statusCode);
    }
}
