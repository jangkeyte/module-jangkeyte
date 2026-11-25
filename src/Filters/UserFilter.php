<?php

namespace JangKeyte\Filters;

use Illuminate\Database\Eloquent\Builder;

class UserFilter extends QueryFilter
{
    public function keyword(mixed $value): Builder
    {
        $term = is_array($value) ? implode(' ', $value) : (string) $value;

        return $this->builder->where(function (Builder $query) use ($term): void {
            $query->where('name', 'like', "%{$term}%")
                ->orWhere('username', 'like', "%{$term}%")
                ->orWhere('email', 'like', "%{$term}%");
        });
    }
}
