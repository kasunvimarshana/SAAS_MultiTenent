<?php

namespace Database\Seeders;

use App\Models\Tenant;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class TenantSeeder extends Seeder
{
    public function run(): void
    {
        $tenant1 = Tenant::create([
            'name' => 'Acme Corp',
            'slug' => 'acme',
            'domain' => 'acme.example.com',
            'is_active' => true,
            'plan' => 'enterprise',
            'settings' => [
                'mail' => [
                    'from_name' => 'Acme Corp',
                    'from_address' => 'noreply@acme.com',
                ],
            ],
        ]);

        User::create([
            'tenant_id' => $tenant1->id,
            'name' => 'Acme Admin',
            'email' => 'admin@acme.com',
            'password' => Hash::make('password'),
            'is_active' => true,
        ]);

        $tenant2 = Tenant::create([
            'name' => 'Beta Inc',
            'slug' => 'beta',
            'domain' => 'beta.example.com',
            'is_active' => true,
            'plan' => 'professional',
        ]);

        User::create([
            'tenant_id' => $tenant2->id,
            'name' => 'Beta Admin',
            'email' => 'admin@beta.com',
            'password' => Hash::make('password'),
            'is_active' => true,
        ]);
    }
}
