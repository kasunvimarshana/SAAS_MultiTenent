<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AuthorizeAbac
{
    /**
     * ABAC (Attribute-Based Access Control) middleware.
     * Evaluates policies based on user attributes, resource attributes, and environment.
     */
    public function handle(Request $request, Closure $next, string $action, string $resource): Response
    {
        $user = $request->user();
        if (!$user) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        $tenant = app('current_tenant');

        // Evaluate ABAC policy
        if (!$this->evaluatePolicy($user, $action, $resource, $request, $tenant)) {
            return response()->json([
                'message' => 'Forbidden: ABAC policy denied access.',
                'action' => $action,
                'resource' => $resource,
            ], 403);
        }

        return $next($request);
    }

    private function evaluatePolicy($user, string $action, string $resource, Request $request, $tenant): bool
    {
        // Super admin bypasses all checks
        if ($user->hasRole('super_admin')) {
            return true;
        }

        // Tenant isolation: user must belong to the current tenant
        if ($tenant && $user->tenant_id !== $tenant->id) {
            return false;
        }

        // RBAC check: user must have the required permission
        $permission = "{$action}_{$resource}";
        if ($user->hasPermission($permission)) {
            return true;
        }

        // ABAC attribute check: evaluate additional attributes
        return $this->evaluateAttributePolicy($user, $action, $resource, $request);
    }

    private function evaluateAttributePolicy($user, string $action, string $resource, Request $request): bool
    {
        // Example: users can only read/update their own resources
        if ($action === 'read' || $action === 'update') {
            $resourceId = $request->route('id') ?? $request->route($resource . '_id');
            if ($resourceId && (string) $user->id === (string) $resourceId) {
                return true;
            }
        }
        return false;
    }
}
