<?php

namespace Modules\Authetication\src\Filters;

use Modules\Authetication\src\Filters\QueryFilter;

class UserFilter extends QueryFilter
{
    protected $filterable = [
        
    ];
    
    public function filterKeyword($value)
    {
        return $this->builder->where('name', 'like', '%' . $value . '%')
                    ->orWhere('email', 'like', '%' . $value . '%');
    }
}