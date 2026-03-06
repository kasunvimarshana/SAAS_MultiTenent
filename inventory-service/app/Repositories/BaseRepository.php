<?php

namespace App\Repositories;

use App\Contracts\Repository\BaseRepositoryInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

abstract class BaseRepository implements BaseRepositoryInterface
{
    protected Model $model;
    protected Builder $query;
    protected array $with = [];
    protected ?string $orderColumn = null;
    protected string $orderDirection = 'asc';

    public function __construct(Model $model)
    {
        $this->model = $model;
        $this->resetQuery();
    }

    protected function resetQuery(): void
    {
        $this->query = $this->model->newQuery();
        if (!empty($this->with)) { $this->query->with($this->with); }
        if ($this->orderColumn) { $this->query->orderBy($this->orderColumn, $this->orderDirection); }
    }

    public function all(array $columns = ['*']): Collection
    {
        $result = $this->query->get($columns);
        $this->resetQuery();
        return $result;
    }

    public function find(int|string $id, array $columns = ['*']): ?Model
    {
        $result = $this->query->find($id, $columns);
        $this->resetQuery();
        return $result;
    }

    public function findBy(string $field, mixed $value, array $columns = ['*']): ?Model
    {
        $result = $this->query->where($field, $value)->first($columns);
        $this->resetQuery();
        return $result;
    }

    public function findAllBy(string $field, mixed $value, array $columns = ['*']): Collection
    {
        $result = $this->query->where($field, $value)->get($columns);
        $this->resetQuery();
        return $result;
    }

    public function create(array $data): Model
    {
        $model = $this->model->create($data);
        $this->resetQuery();
        return $model;
    }

    public function update(int|string $id, array $data): Model
    {
        $model = $this->find($id);
        if (!$model) { throw new \RuntimeException("Model with ID {$id} not found."); }
        $model->update($data);
        $this->resetQuery();
        return $model->fresh();
    }

    public function delete(int|string $id): bool
    {
        $model = $this->find($id);
        if (!$model) { return false; }
        $result = $model->delete();
        $this->resetQuery();
        return $result;
    }

    public function paginate(int $perPage = 15, array $columns = ['*'], int $page = 1): LengthAwarePaginator
    {
        $result = $this->query->paginate($perPage, $columns, 'page', $page);
        $this->resetQuery();
        return $result;
    }

    public function search(string $query, array $searchable = [], array $columns = ['*']): Collection
    {
        if (empty($searchable)) { $searchable = $this->model->getFillable(); }
        $this->query->where(function (Builder $q) use ($query, $searchable) {
            foreach ($searchable as $field) {
                $q->orWhere($field, 'LIKE', '%' . addcslashes($query, '%_\\') . '%');
            }
        });
        $result = $this->query->get($columns);
        $this->resetQuery();
        return $result;
    }

    public function filter(array $filters, array $columns = ['*']): Collection
    {
        foreach ($filters as $field => $value) {
            if (is_array($value)) {
                if (isset($value['operator']) && isset($value['value'])) {
                    $this->query->where($field, $value['operator'], $value['value']);
                } elseif (isset($value['between'])) {
                    $this->query->whereBetween($field, $value['between']);
                } elseif (isset($value['in'])) {
                    $this->query->whereIn($field, $value['in']);
                } else {
                    $this->query->whereIn($field, $value);
                }
            } else {
                $this->query->where($field, $value);
            }
        }
        $result = $this->query->get($columns);
        $this->resetQuery();
        return $result;
    }

    public function orderBy(string $column, string $direction = 'asc'): static
    {
        $this->orderColumn = $column;
        $this->orderDirection = $direction;
        $this->query->orderBy($column, $direction);
        return $this;
    }

    public function with(array $relations): static
    {
        $this->with = $relations;
        $this->query->with($relations);
        return $this;
    }

    public function conditionalPaginate(array $params, array $columns = ['*']): LengthAwarePaginator|Collection
    {
        if (isset($params['search']) && !empty($params['search'])) {
            $searchable = $params['searchable'] ?? $this->model->getFillable();
            $builder = $this->model->newQuery();
            if (!empty($this->with)) { $builder->with($this->with); }
            $builder->where(function (Builder $q) use ($params, $searchable) {
                foreach ($searchable as $field) {
                    $q->orWhere($field, 'LIKE', '%' . addcslashes($params['search'], '%_\\') . '%');
                }
            });
            $this->query = $builder;
        }

        if (isset($params['filters']) && is_array($params['filters'])) {
            foreach ($params['filters'] as $field => $value) {
                if (is_array($value) && isset($value['operator'])) {
                    $this->query->where($field, $value['operator'], $value['value']);
                } else {
                    $this->query->where($field, $value);
                }
            }
        }

        if (isset($params['sort_by'])) {
            $this->query->orderBy($params['sort_by'], $params['sort_direction'] ?? 'asc');
        }

        if (isset($params['per_page'])) {
            $perPage = (int) $params['per_page'];
            $page = (int) ($params['page'] ?? 1);
            $result = $this->query->paginate($perPage, $columns, 'page', $page);
            $this->resetQuery();
            return $result;
        }

        $result = $this->query->get($columns);
        $this->resetQuery();
        return $result;
    }

    public function paginateIterable(iterable $data, array $params): LengthAwarePaginator|Collection
    {
        $collection = collect($data);
        if (isset($params['search']) && !empty($params['search'])) {
            $searchable = $params['searchable'] ?? [];
            $collection = $collection->filter(function ($item) use ($params, $searchable) {
                foreach ($searchable as $field) {
                    $value = is_array($item) ? ($item[$field] ?? '') : ($item->{$field} ?? '');
                    if (str_contains(strtolower((string) $value), strtolower($params['search']))) { return true; }
                }
                return false;
            })->values();
        }
        if (isset($params['sort_by'])) {
            $field = $params['sort_by'];
            $direction = $params['sort_direction'] ?? 'asc';
            $collection = $direction === 'desc' ? $collection->sortByDesc($field)->values() : $collection->sortBy($field)->values();
        }
        if (!isset($params['per_page'])) { return $collection; }
        $perPage = (int) $params['per_page'];
        $page = (int) ($params['page'] ?? 1);
        $total = $collection->count();
        $items = $collection->forPage($page, $perPage)->values();
        return new LengthAwarePaginator($items, $total, $perPage, $page, ['path' => request()->url(), 'query' => request()->query()]);
    }

    public function transaction(callable $callback): mixed
    {
        return DB::transaction($callback);
    }
}
