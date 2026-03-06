<?php

namespace App\DTOs;

use App\Models\Order;

class OrderDTO
{
    public function __construct(
        public readonly int|string $id,
        public readonly string $orderNumber,
        public readonly int|string $tenantId,
        public readonly int|string $userId,
        public readonly string $status,
        public readonly float $totalAmount,
        public readonly string $currency,
        public readonly array $items = [],
    ) {}

    public static function fromModel(Order $order): self
    {
        return new self(
            id: $order->id,
            orderNumber: $order->order_number,
            tenantId: $order->tenant_id,
            userId: $order->user_id,
            status: $order->status,
            totalAmount: (float) $order->total_amount,
            currency: $order->currency ?? 'USD',
            items: $order->relationLoaded('items') ? $order->items->toArray() : [],
        );
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'order_number' => $this->orderNumber,
            'tenant_id' => $this->tenantId,
            'user_id' => $this->userId,
            'status' => $this->status,
            'total_amount' => $this->totalAmount,
            'currency' => $this->currency,
            'items' => $this->items,
        ];
    }
}
