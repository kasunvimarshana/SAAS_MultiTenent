<?php

namespace App\Repositories;

use App\Models\Inventory;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class InventoryRepository extends BaseRepository
{
    public function __construct(Inventory $model)
    {
        parent::__construct($model);
    }

    public function findByProductId(int|string $productId): ?Inventory
    {
        return $this->findBy('product_id', $productId);
    }

    public function findByTenantAndProduct(int|string $tenantId, int|string $productId): ?Inventory
    {
        $result = $this->query->where('tenant_id', $tenantId)->where('product_id', $productId)->first();
        $this->resetQuery();
        return $result;
    }

    public function getLowStockItems(int|string $tenantId): Collection
    {
        $result = $this->query
            ->where('tenant_id', $tenantId)
            ->whereRaw('quantity <= reorder_level')
            ->get();
        $this->resetQuery();
        return $result;
    }

    public function adjustQuantity(int|string $inventoryId, int $delta): Inventory
    {
        return DB::transaction(function () use ($inventoryId, $delta) {
            $inventory = $this->query->lockForUpdate()->find($inventoryId);
            if (!$inventory) {
                throw new \RuntimeException("Inventory item {$inventoryId} not found.");
            }
            $previousQty = $inventory->quantity;
            $newQty = $previousQty + $delta;
            if ($newQty < 0) {
                throw new \RuntimeException("Insufficient stock. Available: {$previousQty}, Requested: " . abs($delta));
            }
            $inventory->update(['quantity' => $newQty]);
            $this->resetQuery();
            return $inventory->fresh();
        });
    }

    public function reserveQuantity(int|string $inventoryId, int $quantity): bool
    {
        return DB::transaction(function () use ($inventoryId, $quantity) {
            $inventory = $this->query->lockForUpdate()->find($inventoryId);
            if (!$inventory) { return false; }
            $available = $inventory->quantity - $inventory->reserved_quantity;
            if ($available < $quantity) {
                throw new \RuntimeException("Insufficient available stock. Available: {$available}, Requested: {$quantity}");
            }
            $inventory->update(['reserved_quantity' => $inventory->reserved_quantity + $quantity]);
            $this->resetQuery();
            return true;
        });
    }

    public function releaseReservation(int|string $inventoryId, int $quantity): bool
    {
        return DB::transaction(function () use ($inventoryId, $quantity) {
            $inventory = $this->query->lockForUpdate()->find($inventoryId);
            if (!$inventory) { return false; }
            $newReserved = max(0, $inventory->reserved_quantity - $quantity);
            $inventory->update(['reserved_quantity' => $newReserved]);
            $this->resetQuery();
            return true;
        });
    }

    public function searchByProductName(string $name, int|string $tenantId): Collection
    {
        $result = $this->query
            ->where('tenant_id', $tenantId)
            ->where('product_name', 'LIKE', '%' . addcslashes($name, '%_\\') . '%')
            ->get();
        $this->resetQuery();
        return $result;
    }
}
