<?php

namespace App\Contracts\Repository;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

interface BaseRepositoryInterface
{
    public function all(array $columns = ['*']): Collection;
    public function find(int|string $id, array $columns = ['*']): ?Model;
    public function findBy(string $field, mixed $value, array $columns = ['*']): ?Model;
    public function findAllBy(string $field, mixed $value, array $columns = ['*']): Collection;
    public function create(array $data): Model;
    public function update(int|string $id, array $data): Model;
    public function delete(int|string $id): bool;
    public function paginate(int $perPage = 15, array $columns = ['*'], int $page = 1): LengthAwarePaginator;
    public function search(string $query, array $searchable = [], array $columns = ['*']): Collection;
    public function filter(array $filters, array $columns = ['*']): Collection;
    public function orderBy(string $column, string $direction = 'asc'): static;
    public function with(array $relations): static;
    public function conditionalPaginate(array $params, array $columns = ['*']): LengthAwarePaginator|Collection;
    public function transaction(callable $callback): mixed;
}
