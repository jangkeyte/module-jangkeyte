<?php

namespace JangKeyte\Traits;

use Illuminate\Database\Eloquent\Builder;
use JangKeyte\Filters\QueryFilter;

trait Filterable
{
    public function scopeFilter(Builder $query, QueryFilter $filters): Builder
    {
        return $filters->apply($query);
    }
}
