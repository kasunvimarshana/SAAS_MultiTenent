<?php

namespace App\Services;

use App\Events\UserCreated;
use App\Repositories\UserRepository;
use App\DTOs\UserDTO;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Log;

class UserService extends BaseService
{
    public function __construct(
        protected readonly UserRepository $repository,
        private readonly WebhookService $webhookService
    ) {
        parent::__construct($repository);
    }

    public function createUser(array $data): Model
    {
        return $this->repository->transaction(function () use ($data) {
            $data['password'] = Hash::make($data['password']);
            $user = $this->repository->create($data);

            // Dispatch domain event
            Event::dispatch(new UserCreated($user));

            // Trigger webhook
            $this->webhookService->triggerWebhook($user->tenant_id, 'user.created', [
                'user_id' => $user->id,
                'email' => $user->email,
                'name' => $user->name,
            ]);

            Log::info('UserService: User created', ['user_id' => $user->id, 'tenant_id' => $user->tenant_id]);
            return $user;
        });
    }

    public function updateUser(int|string $id, array $data): Model
    {
        return $this->repository->transaction(function () use ($id, $data) {
            if (isset($data['password'])) {
                $data['password'] = Hash::make($data['password']);
            }
            return $this->repository->update($id, $data);
        });
    }

    public function getUsersByTenant(int|string $tenantId, array $params = []): mixed
    {
        $params['filters']['tenant_id'] = $tenantId;
        return $this->repository->conditionalPaginate($params);
    }

    public function assignRole(int|string $userId, int|string $roleId): bool
    {
        return $this->repository->transaction(function () use ($userId, $roleId) {
            $user = $this->repository->find($userId);
            if (!$user) {
                throw new \RuntimeException("User not found: {$userId}");
            }
            $user->roles()->syncWithoutDetaching([$roleId]);
            return true;
        });
    }

    public function getUserDTO(int|string $userId): ?UserDTO
    {
        $user = $this->repository->find($userId);
        if (!$user) {
            return null;
        }
        return UserDTO::fromModel($user);
    }
}
