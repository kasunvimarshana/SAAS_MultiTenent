<?php

namespace App\DTOs;

use App\Models\Inventory;

class InventoryDTO
{
    public function __construct(
        public readonly int|string $id,
        public readonly int|string $tenantId,
        public readonly int|string $productId,
        public readonly string $productName,
        public readonly string $productSku,
        public readonly int $quantity,
        public readonly int $reservedQuantity,
        public readonly int $availableQuantity,
        public readonly int $reorderLevel,
    ) {}

    public static function fromModel(Inventory $inventory): self
    {
        return new self(
            id: $inventory->id,
            tenantId: $inventory->tenant_id,
            productId: $inventory->product_id,
            productName: $inventory->product_name ?? '',
            productSku: $inventory->product_sku ?? '',
            quantity: $inventory->quantity,
            reservedQuantity: $inventory->reserved_quantity,
            availableQuantity: $inventory->quantity - $inventory->reserved_quantity,
            reorderLevel: $inventory->reorder_level ?? 0,
        );
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'tenant_id' => $this->tenantId,
            'product_id' => $this->productId,
            'product_name' => $this->productName,
            'product_sku' => $this->productSku,
            'quantity' => $this->quantity,
            'reserved_quantity' => $this->reservedQuantity,
            'available_quantity' => $this->availableQuantity,
            'reorder_level' => $this->reorderLevel,
        ];
    }
}
