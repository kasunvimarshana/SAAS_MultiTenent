<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AuthorizeAbac
{
    public function handle(Request $request, Closure $next, string $action, string $resource): Response
    {
        $user = $request->user();
        if (!$user) { return response()->json(['message' => 'Unauthenticated.'], 401); }
        $tenant = app('current_tenant');
        if (!$this->evaluatePolicy($user, $action, $resource, $request, $tenant)) {
            return response()->json(['message' => 'Forbidden: ABAC policy denied access.', 'action' => $action, 'resource' => $resource], 403);
        }
        return $next($request);
    }

    private function evaluatePolicy($user, string $action, string $resource, Request $request, $tenant): bool
    {
        if ($user->hasRole('super_admin')) { return true; }
        if ($tenant && $user->tenant_id !== $tenant->id) { return false; }
        return $user->hasPermission("{$action}_{$resource}");
    }
}
