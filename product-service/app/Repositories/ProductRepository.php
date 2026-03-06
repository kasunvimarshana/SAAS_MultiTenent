<?php

namespace App\Repositories;

use App\Models\Product;
use Illuminate\Support\Collection;

class ProductRepository extends BaseRepository
{
    public function __construct(Product $model)
    {
        parent::__construct($model);
    }

    public function findBySku(string $sku): ?Product
    {
        return $this->findBy('sku', $sku);
    }

    public function findByTenantAndSku(int|string $tenantId, string $sku): ?Product
    {
        $result = $this->query->where('tenant_id', $tenantId)->where('sku', $sku)->first();
        $this->resetQuery();
        return $result;
    }

    public function getByTenant(int|string $tenantId): Collection
    {
        return $this->findAllBy('tenant_id', $tenantId);
    }

    public function searchByName(string $name, int|string $tenantId): Collection
    {
        $result = $this->query
            ->where('tenant_id', $tenantId)
            ->where('name', 'LIKE', '%' . addcslashes($name, '%_\\') . '%')
            ->get();
        $this->resetQuery();
        return $result;
    }
}
