<?php

namespace JangKeyte\Repositories\Permission;

use Illuminate\Database\Eloquent\Collection;
use JangKeyte\Contracts\RepositoryInterface;

interface PermissionRepositoryInterface extends RepositoryInterface
{
    public function allOrdered(): Collection;
}
