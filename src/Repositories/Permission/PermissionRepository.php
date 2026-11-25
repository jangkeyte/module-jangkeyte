<?php

namespace JangKeyte\Repositories\Permission;

use Illuminate\Database\Eloquent\Collection;
use JangKeyte\Models\Permission;
use JangKeyte\Repositories\BaseRepository;

class PermissionRepository extends BaseRepository implements PermissionRepositoryInterface
{
    public function __construct(Permission $model)
    {
        parent::__construct($model);
    }

    public function allOrdered(): Collection
    {
        return $this->model->newQuery()->orderBy('group')->orderBy('id')->get();
    }
}
