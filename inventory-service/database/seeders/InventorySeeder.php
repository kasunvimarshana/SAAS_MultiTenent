<?php

namespace Database\Seeders;

use App\Models\Inventory;
use App\Models\Tenant;
use App\Models\Warehouse;
use Illuminate\Database\Seeder;

class InventorySeeder extends Seeder
{
    public function run(): void
    {
        $tenant = Tenant::first();
        if (!$tenant) { return; }

        $warehouse = Warehouse::create(['tenant_id' => $tenant->id, 'name' => 'Main Warehouse', 'code' => 'WH-001', 'is_active' => true]);

        $items = [
            ['product_id' => 1, 'product_name' => 'Widget A', 'product_sku' => 'WGT-A-001', 'quantity' => 100, 'reorder_level' => 10],
            ['product_id' => 2, 'product_name' => 'Widget B', 'product_sku' => 'WGT-B-001', 'quantity' => 50, 'reorder_level' => 5],
            ['product_id' => 3, 'product_name' => 'Gadget X', 'product_sku' => 'GDG-X-001', 'quantity' => 8, 'reorder_level' => 10],
        ];

        foreach ($items as $item) {
            Inventory::create(array_merge($item, [
                'tenant_id' => $tenant->id,
                'warehouse_id' => $warehouse->id,
                'reserved_quantity' => 0,
                'reorder_quantity' => 20,
            ]));
        }
    }
}
