<?php

namespace JangKeyte\Repositories;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use JangKeyte\Contracts\RepositoryInterface;
use JangKeyte\Filters\QueryFilter;

abstract class BaseRepository implements RepositoryInterface
{
    protected Model $model;

    public function __construct(Model $model)
    {
        $this->model = $model;
    }

    public function all(int $perPage = 15): LengthAwarePaginator
    {
        return $this->model->newQuery()->latest('id')->paginate($perPage)->withQueryString();
    }

    public function get(int $limit = 100): Collection
    {
        return $this->model->newQuery()->latest('id')->limit($limit)->get();
    }

    public function find(int|string|null $id): Model
    {
        if ($id === null || $id === '') {
            return $this->model->newInstance();
        }

        return $this->model->newQuery()->whereKey($id)->firstOrNew();
    }

    public function findOrFail(int|string $id): Model
    {
        return $this->model->newQuery()->findOrFail($id);
    }

    public function findBy(string $attribute, mixed $value): Model
    {
        return $this->model->newQuery()->where($attribute, $value)->firstOrNew();
    }

    public function create(array $data): Model
    {
        return $this->model->newQuery()->create($data);
    }

    public function update(int|string $id, array $data): Model
    {
        $record = $this->findOrFail($id);
        $record->fill($data);
        $record->save();

        return $record;
    }

    public function updateOrCreate(array $attributes, array $values = []): Model
    {
        return $this->model->newQuery()->updateOrCreate($attributes, $values);
    }

    public function delete(int|string $id): bool
    {
        $record = $this->findOrFail($id);

        return (bool) $record->delete();
    }

    public function filter(QueryFilter $filter, int $perPage = 15): LengthAwarePaginator
    {
        return $this->model->newQuery()
            ->filter($filter)
            ->latest('id')
            ->paginate($perPage)
            ->withQueryString();
    }

    public function queryFiltered(
        ?QueryFilter $filter,
        ?string $sortBy = null,
        string $sortDir = 'desc',
        bool $onlyTrashed = false,
        array $with = [],
    ): Builder {
        $query = $this->model->newQuery();

        if ($with !== []) {
            $query->with($with);
        }

        if ($onlyTrashed && $this->usesSoftDeletes()) {
            $query->onlyTrashed();
        }

        if ($filter !== null) {
            $query = $filter->apply($query);
        }

        $sortColumn = $this->resolveSortColumn($sortBy);
        $direction = $this->resolveSortDirection($sortDir);

        $query->orderBy($sortColumn, $direction);

        $primaryKey = $this->model->getKeyName();
        if ($sortColumn !== $primaryKey) {
            $query->orderBy($primaryKey, $direction);
        }

        return $query;
    }

    public function paginateFiltered(
        ?QueryFilter $filter,
        int $perPage = 15,
        ?string $sortBy = null,
        string $sortDir = 'desc',
        bool $onlyTrashed = false,
        array $with = [],
    ): LengthAwarePaginator {
        return $this->queryFiltered($filter, $sortBy, $sortDir, $onlyTrashed, $with)
            ->paginate($perPage)
            ->withQueryString();
    }

    public function bulkDelete(array $ids): int
    {
        if ($ids === []) {
            return 0;
        }

        $cleanIds = $this->sanitizeKeyIds($ids);
        if ($cleanIds === []) {
            return 0;
        }

        $records = $this->model->newQuery()->whereKey($cleanIds)->get();
        $deletedCount = 0;

        foreach ($records as $record) {
            if ($record->delete()) {
                $deletedCount++;
            }
        }

        return $deletedCount;
    }

    public function restore(int|string $id): bool
    {
        if (! $this->usesSoftDeletes()) {
            return false;
        }

        $record = $this->model->newQuery()->withTrashed()->findOrFail($id);

        return (bool) $record->restore();
    }

    public function forceDelete(int|string $id): bool
    {
        $query = $this->model->newQuery();

        if ($this->usesSoftDeletes()) {
            $query->withTrashed();
        }

        $record = $query->findOrFail($id);

        return (bool) $record->forceDelete();
    }

    protected function usesSoftDeletes(): bool
    {
        return in_array(SoftDeletes::class, class_uses_recursive($this->model), true);
    }

    protected function resolveSortColumn(?string $sortBy): string
    {
        $sortable = $this->model->sortable ?? ['id', 'created_at'];
        if (! is_array($sortable) || $sortable === []) {
            $sortable = ['id', 'created_at'];
        }

        if ($sortBy !== null && in_array($sortBy, $sortable, true)) {
            return $sortBy;
        }

        return 'id';
    }

    protected function resolveSortDirection(string $sortDir): string
    {
        return strtolower($sortDir) === 'asc' ? 'asc' : 'desc';
    }

    /**
     * @param  array<int, mixed>  $ids
     * @return array<int, int|string>
     */
    protected function sanitizeKeyIds(array $ids): array
    {
        $keyType = $this->model->getKeyType();
        $clean = [];

        foreach ($ids as $id) {
            if (! is_int($id) && ! is_string($id)) {
                continue;
            }

            if ($keyType === 'int' || $keyType === 'integer') {
                $filtered = filter_var($id, FILTER_VALIDATE_INT, [
                    'options' => ['min_range' => 1],
                ]);
                if ($filtered !== false) {
                    $clean[] = (int) $filtered;
                }
            } else {
                $trimmed = trim((string) $id);
                if ($trimmed !== '') {
                    $clean[] = $trimmed;
                }
            }
        }

        return array_values(array_unique($clean));
    }
}
