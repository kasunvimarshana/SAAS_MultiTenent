<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;

class HealthCheckController extends BaseController
{
    public function check(): JsonResponse
    {
        $checks = [];
        $healthy = true;

        try {
            DB::connection()->getPdo();
            $checks['database'] = ['status' => 'healthy'];
        } catch (\Throwable $e) {
            $checks['database'] = ['status' => 'unhealthy', 'message' => $e->getMessage()];
            $healthy = false;
        }

        try {
            Cache::put('health_check', 'ok', 10);
            $checks['cache'] = ['status' => Cache::get('health_check') === 'ok' ? 'healthy' : 'unhealthy'];
        } catch (\Throwable $e) {
            $checks['cache'] = ['status' => 'unhealthy', 'message' => $e->getMessage()];
            $healthy = false;
        }

        $checks['app'] = ['status' => 'healthy', 'name' => config('app.name'), 'version' => '1.0.0'];

        return response()->json([
            'status' => $healthy ? 'healthy' : 'unhealthy',
            'service' => 'inventory-service',
            'timestamp' => now()->toISOString(),
            'checks' => $checks,
        ], $healthy ? 200 : 503);
    }
}
