<?php

namespace Lorisleiva\LaravelSearchString\Visitor;

use Illuminate\Database\Eloquent\Builder;
use Lorisleiva\LaravelSearchString\Exceptions\InvalidSearchStringException;

class ExtractOffsetQueryVisitor extends ExtractSpecialQueryVisitor
{
    protected $builder;

    public function __construct(Builder $builder, $key, $operator = null, $value = null)
    {
        $this->builder = $builder;
        parent::__construct($key, $operator, $value);
    }

    protected function useSpecialQuery($query, $lastQuery)
    {
        if (! ctype_digit($query->value)) {
            throw new InvalidSearchStringException;
        }

        $this->builder->offset($query->value);
    }
}