<?php

namespace App\Repositories;

use App\Models\Order;

class OrderRepository extends BaseRepository
{
    public function __construct(Order $model)
    {
        parent::__construct($model);
    }

    public function findByOrderNumber(string $orderNumber): ?Order
    {
        return $this->findBy('order_number', $orderNumber);
    }

    public function getByTenant(int|string $tenantId, array $params = []): mixed
    {
        $params['filters']['tenant_id'] = $tenantId;
        return $this->conditionalPaginate($params);
    }

    public function getByUser(int|string $userId, int|string $tenantId): \Illuminate\Support\Collection
    {
        $result = $this->query
            ->where('tenant_id', $tenantId)
            ->where('user_id', $userId)
            ->with('items')
            ->get();
        $this->resetQuery();
        return $result;
    }
}
