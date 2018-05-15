<?php

namespace Lorisleiva\LaravelSearchString\Visitor;

use Illuminate\Database\Eloquent\Builder;

class ExtractSelectQueryVisitor extends ExtractSpecialQueryVisitor
{
    protected $builder;

    public function __construct(Builder $builder, $key, $operator = null, $value = null)
    {
        $this->builder = $builder;
        parent::__construct($key, $operator, $value);
    }

    protected function useSpecialQuery($query, $lastQuery)
    {
        $columns = array_wrap($query->value);

        if (! in_array($query->operator, ['!=', 'not in'])) {
            return $this->builder->select($columns);
        }

        // TODO: Change columns with a method provided by the trait used on the model.
        // This work around is temporary.
        $model = $this->builder->getModel();
        $this->builder->select(array_values(array_diff($model->columns, $columns)));
    }
}