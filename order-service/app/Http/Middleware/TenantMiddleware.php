<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Models\Tenant;
use App\Services\TenantConfigService;
use Symfony\Component\HttpFoundation\Response;

class TenantMiddleware
{
    public function __construct(private readonly TenantConfigService $tenantConfigService) {}

    public function handle(Request $request, Closure $next): Response
    {
        $tenantId = $request->header('X-Tenant-ID')
            ?? $request->query('tenant_id')
            ?? ($request->user() ? $request->user()->tenant_id : null);

        if (!$tenantId) { return response()->json(['message' => 'Tenant ID is required.'], 400); }

        $tenant = Tenant::where('id', $tenantId)->where('is_active', true)->first();
        if (!$tenant) { return response()->json(['message' => 'Tenant not found or inactive.'], 404); }

        $this->tenantConfigService->applyTenantConfig($tenant);
        app()->instance('current_tenant', $tenant);
        $request->attributes->set('tenant', $tenant);

        return $next($request);
    }
}
