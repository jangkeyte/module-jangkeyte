<?php

namespace JangKeyte\Filters;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

abstract class QueryFilter
{
    protected Request $request;

    protected Builder $builder;

    protected string $delimiter = ',';

    public function __construct(Request $request)
    {
        $this->request = $request;
    }

    public function apply(Builder $builder): Builder
    {
        $this->builder = $builder;

        foreach ($this->filters() as $name => $value) {
            if ($value === null || $value === '') {
                continue;
            }

            $method = Str::camel($name);
            if (method_exists($this, $method)) {
                $this->{$method}($this->paramToArray($value));
            }
        }

        return $this->builder;
    }

    public function filters(): array
    {
        return $this->request->query();
    }

    protected function paramToArray(mixed $param): mixed
    {
        if (is_string($param) && str_contains($param, $this->delimiter)) {
            return explode($this->delimiter, $param);
        }

        return $param;
    }
}
