<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\Role;
use App\Models\Tenant;
use Illuminate\Database\Seeder;

class RolePermissionSeeder extends Seeder
{
    public function run(): void
    {
        // Create permissions
        $resources = ['users', 'products', 'inventory', 'orders', 'tenants', 'webhooks'];
        $actions = ['create', 'read', 'update', 'delete', 'list'];

        $permissions = [];
        foreach ($resources as $resource) {
            foreach ($actions as $action) {
                $permissions["{$action}_{$resource}"] = Permission::create([
                    'name' => "{$action}_{$resource}",
                    'description' => ucfirst($action) . ' ' . ucfirst($resource),
                    'resource' => $resource,
                    'action' => $action,
                ]);
            }
        }

        // Create global roles (no tenant)
        $superAdminRole = Role::create([
            'name' => 'super_admin',
            'description' => 'Super Administrator',
            'tenant_id' => null,
        ]);
        $superAdminRole->permissions()->attach(array_values($permissions));

        // Create tenant-specific roles
        Tenant::all()->each(function ($tenant) use ($permissions) {
            $adminRole = Role::create([
                'name' => 'admin',
                'description' => 'Tenant Administrator',
                'tenant_id' => $tenant->id,
            ]);
            $adminRole->permissions()->attach(array_values($permissions));

            $managerRole = Role::create([
                'name' => 'manager',
                'description' => 'Manager',
                'tenant_id' => $tenant->id,
            ]);
            $managerRole->permissions()->attach(array_filter($permissions, function ($perm) {
                return in_array($perm->action, ['read', 'list', 'create', 'update']);
            }));

            $viewerRole = Role::create([
                'name' => 'viewer',
                'description' => 'Viewer (read-only)',
                'tenant_id' => $tenant->id,
            ]);
            $viewerRole->permissions()->attach(array_filter($permissions, function ($perm) {
                return in_array($perm->action, ['read', 'list']);
            }));
        });
    }
}
