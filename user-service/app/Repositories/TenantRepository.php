<?php

namespace App\Repositories;

use App\Models\Tenant;

class TenantRepository extends BaseRepository
{
    public function __construct(Tenant $model)
    {
        parent::__construct($model);
    }

    public function findBySlug(string $slug): ?Tenant
    {
        return $this->findBy('slug', $slug);
    }

    public function findByDomain(string $domain): ?Tenant
    {
        return $this->findBy('domain', $domain);
    }
}
