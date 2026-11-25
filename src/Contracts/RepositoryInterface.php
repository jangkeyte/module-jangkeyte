<?php

namespace JangKeyte\Contracts;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use JangKeyte\Filters\QueryFilter;

interface RepositoryInterface
{
    public function all(int $perPage = 15): LengthAwarePaginator;

    public function get(int $limit = 100): Collection;

    public function find(int|string|null $id): Model;

    public function findOrFail(int|string $id): Model;

    public function findBy(string $attribute, mixed $value): Model;

    public function create(array $data): Model;

    public function update(int|string $id, array $data): Model;

    public function updateOrCreate(array $attributes, array $values = []): Model;

    public function delete(int|string $id): bool;

    public function filter(QueryFilter $filter, int $perPage = 15): LengthAwarePaginator;

    /**
     * @param  array<int, string>  $with
     */
    public function queryFiltered(
        ?QueryFilter $filter,
        ?string $sortBy = null,
        string $sortDir = 'desc',
        bool $onlyTrashed = false,
        array $with = [],
    ): Builder;

    /**
     * @param  array<int, string>  $with
     */
    public function paginateFiltered(
        ?QueryFilter $filter,
        int $perPage = 15,
        ?string $sortBy = null,
        string $sortDir = 'desc',
        bool $onlyTrashed = false,
        array $with = [],
    ): LengthAwarePaginator;

    /**
     * @param  array<int, int|string>  $ids
     */
    public function bulkDelete(array $ids): int;

    public function restore(int|string $id): bool;

    public function forceDelete(int|string $id): bool;
}
