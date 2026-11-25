<?php

namespace JangKeyte\Repositories\User;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use JangKeyte\Contracts\RepositoryInterface;
use JangKeyte\Models\User;

interface UserRepositoryInterface extends RepositoryInterface
{
    public function paginateWithRoles(int $perPage = 20): LengthAwarePaginator;

    public function findByUsernameOrEmail(string $identifier): ?User;
}
