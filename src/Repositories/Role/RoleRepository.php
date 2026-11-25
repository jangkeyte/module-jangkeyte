<?php

namespace JangKeyte\Repositories\Role;

use Illuminate\Database\Eloquent\Collection;
use JangKeyte\Models\Role;
use JangKeyte\Repositories\BaseRepository;

class RoleRepository extends BaseRepository implements RoleRepositoryInterface
{
    public function __construct(Role $model)
    {
        parent::__construct($model);
    }

    public function allRoles(): Collection
    {
        return $this->model->newQuery()->withCount('permissions', 'users')->orderBy('id')->get();
    }
}
