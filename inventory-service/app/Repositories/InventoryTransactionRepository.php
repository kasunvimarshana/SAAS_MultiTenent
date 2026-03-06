<?php

namespace App\Repositories;

use App\Models\InventoryTransaction;

class InventoryTransactionRepository extends BaseRepository
{
    public function __construct(InventoryTransaction $model)
    {
        parent::__construct($model);
    }

    public function getByInventory(int|string $inventoryId): \Illuminate\Support\Collection
    {
        return $this->findAllBy('inventory_id', $inventoryId);
    }
}
