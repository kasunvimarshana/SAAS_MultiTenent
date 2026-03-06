<?php

namespace App\DTOs;

use App\Models\Product;

class ProductDTO
{
    public function __construct(
        public readonly int|string $id,
        public readonly string $name,
        public readonly string $sku,
        public readonly float $price,
        public readonly int|string $tenantId,
        public readonly bool $isActive,
        public readonly ?string $description = null,
        public readonly ?string $category = null,
        public readonly ?string $brand = null,
        public readonly string $createdAt = '',
    ) {}

    public static function fromModel(Product $product): self
    {
        return new self(
            id: $product->id,
            name: $product->name,
            sku: $product->sku,
            price: (float) $product->price,
            tenantId: $product->tenant_id,
            isActive: $product->is_active ?? true,
            description: $product->description,
            category: $product->category,
            brand: $product->brand,
            createdAt: $product->created_at?->toISOString() ?? '',
        );
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'sku' => $this->sku,
            'price' => $this->price,
            'tenant_id' => $this->tenantId,
            'is_active' => $this->isActive,
            'description' => $this->description,
            'category' => $this->category,
            'brand' => $this->brand,
            'created_at' => $this->createdAt,
        ];
    }
}
