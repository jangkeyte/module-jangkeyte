<?php

namespace JangKeyte\Repositories\Role;

use Illuminate\Database\Eloquent\Collection;
use JangKeyte\Contracts\RepositoryInterface;

interface RoleRepositoryInterface extends RepositoryInterface
{
    public function allRoles(): Collection;
}
