<?php

namespace App\Services;

use App\Contracts\Repository\BaseRepositoryInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

abstract class BaseService
{
    public function __construct(protected readonly BaseRepositoryInterface $repository) {}

    public function getAll(array $columns = ['*']): Collection
    {
        return $this->repository->all($columns);
    }

    public function getById(int|string $id, array $columns = ['*']): ?Model
    {
        return $this->repository->find($id, $columns);
    }

    public function create(array $data): Model
    {
        return $this->repository->transaction(fn () => $this->repository->create($data));
    }

    public function update(int|string $id, array $data): Model
    {
        return $this->repository->transaction(fn () => $this->repository->update($id, $data));
    }

    public function delete(int|string $id): bool
    {
        return $this->repository->transaction(fn () => $this->repository->delete($id));
    }

    public function list(array $params = []): LengthAwarePaginator|Collection
    {
        return $this->repository->conditionalPaginate($params);
    }
}
