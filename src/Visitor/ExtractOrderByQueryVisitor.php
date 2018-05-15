<?php

namespace Lorisleiva\LaravelSearchString\Visitor;

use Illuminate\Database\Eloquent\Builder;

class ExtractOrderByQueryVisitor extends ExtractSpecialQueryVisitor
{
    protected $builder;

    public function __construct(Builder $builder, $key, $operator = null, $value = null)
    {
        $this->builder = $builder;
        parent::__construct($key, $operator, $value);
    }

    protected function useSpecialQuery($query, $lastQuery)
    {
        $this->builder->getQuery()->orders = null;

        collect($query->value)->each(function ($value) {
            $desc = starts_with($value, '-') ? 'desc' : 'asc';
            $column = starts_with($value, '-') ? str_after($value, '-') : $value;
            $this->builder->orderBy($column, $desc);
        });
    }
}