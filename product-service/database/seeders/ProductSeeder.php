<?php

namespace Database\Seeders;

use App\Models\Product;
use App\Models\Tenant;
use Illuminate\Database\Seeder;

class ProductSeeder extends Seeder
{
    public function run(): void
    {
        $tenant = Tenant::first();
        if (!$tenant) {
            return;
        }

        $products = [
            ['name' => 'Widget A', 'sku' => 'WGT-A-001', 'price' => 19.99, 'category' => 'Widgets', 'unit' => 'piece'],
            ['name' => 'Widget B', 'sku' => 'WGT-B-001', 'price' => 29.99, 'category' => 'Widgets', 'unit' => 'piece'],
            ['name' => 'Gadget X', 'sku' => 'GDG-X-001', 'price' => 99.99, 'category' => 'Gadgets', 'unit' => 'piece'],
            ['name' => 'Gadget Y', 'sku' => 'GDG-Y-001', 'price' => 149.99, 'category' => 'Gadgets', 'unit' => 'piece'],
            ['name' => 'Component Z', 'sku' => 'CMP-Z-001', 'price' => 4.99, 'category' => 'Components', 'unit' => 'pack'],
        ];

        foreach ($products as $product) {
            Product::create(array_merge($product, ['tenant_id' => $tenant->id, 'is_active' => true]));
        }
    }
}
