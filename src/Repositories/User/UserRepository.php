<?php

namespace JangKeyte\Repositories\User;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use JangKeyte\Models\User;
use JangKeyte\Repositories\BaseRepository;

class UserRepository extends BaseRepository implements UserRepositoryInterface
{
    public function __construct(User $model)
    {
        parent::__construct($model);
    }

    public function paginateWithRoles(int $perPage = 20): LengthAwarePaginator
    {
        return $this->model->newQuery()
            ->with('roles')
            ->latest('id')
            ->paginate($perPage)
            ->withQueryString();
    }

    public function findByUsernameOrEmail(string $identifier): ?User
    {
        return $this->model->newQuery()
            ->where('username', $identifier)
            ->orWhere('email', $identifier)
            ->first();
    }
}
