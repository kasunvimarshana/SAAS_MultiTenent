<?php

namespace App\Repositories;

use App\Models\OrderItem;

class OrderItemRepository extends BaseRepository
{
    public function __construct(OrderItem $model)
    {
        parent::__construct($model);
    }

    public function getByOrder(int|string $orderId): \Illuminate\Support\Collection
    {
        return $this->findAllBy('order_id', $orderId);
    }
}
