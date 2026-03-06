<?php

namespace App\Repositories;

use App\Models\User;

class UserRepository extends BaseRepository
{
    public function __construct(User $model)
    {
        parent::__construct($model);
    }

    public function findByEmail(string $email): ?User
    {
        return $this->findBy('email', $email);
    }

    public function findByTenant(int|string $tenantId): \Illuminate\Support\Collection
    {
        return $this->findAllBy('tenant_id', $tenantId);
    }
}
