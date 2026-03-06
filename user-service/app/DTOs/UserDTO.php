<?php

namespace App\DTOs;

use App\Models\User;

class UserDTO
{
    public function __construct(
        public readonly int|string $id,
        public readonly string $name,
        public readonly string $email,
        public readonly int|string $tenantId,
        public readonly bool $isActive,
        public readonly array $roles = [],
        public readonly string $createdAt = '',
    ) {}

    public static function fromModel(User $user): self
    {
        return new self(
            id: $user->id,
            name: $user->name,
            email: $user->email,
            tenantId: $user->tenant_id,
            isActive: $user->is_active ?? true,
            roles: $user->relationLoaded('roles') ? $user->roles->pluck('name')->toArray() : [],
            createdAt: $user->created_at?->toISOString() ?? '',
        );
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'tenant_id' => $this->tenantId,
            'is_active' => $this->isActive,
            'roles' => $this->roles,
            'created_at' => $this->createdAt,
        ];
    }
}
