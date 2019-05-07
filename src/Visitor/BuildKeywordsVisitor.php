<?php

namespace Lorisleiva\LaravelSearchString\Visitor;

use Illuminate\Database\Eloquent\Builder;
use Lorisleiva\LaravelSearchString\Exceptions\InvalidSearchStringException;
use Lorisleiva\LaravelSearchString\Parser\QuerySymbol;

class BuildKeywordsVisitor extends Visitor
{
    protected $manager;
    protected $builder;

    public function __construct($manager, $builder)
    {
        $this->manager = $manager;
        $this->builder = $builder;
    }

    public function visitQuery(QuerySymbol $query)
    {
        if ($rule = $this->manager->getRuleForQuery($query, 'keywords')) {
            $this->buildKeyword($rule->column, $query);
        }
        
        return $query;
    }

    public function buildKeyword($keyword, $query)
    {
        $methodName = 'build' . title_case(camel_case($keyword)) . 'Keyword';

        return $this->$methodName($query);
    }

    protected function buildOrderByKeyword(QuerySymbol $query)
    {
        $this->builder->getQuery()->orders = null;

        collect($query->value)->each(function ($value) {
            $desc = starts_with($value, '-') ? 'desc' : 'asc';
            $column = starts_with($value, '-') ? str_after($value, '-') : $value;
            $this->builder->orderBy($column, $desc);
        });
    }

    protected function buildSelectKeyword(QuerySymbol $query)
    {
        $columns = array_wrap($query->value);

        $columns = in_array($query->operator, ['!=', 'not in'])
            ? $this->manager->getColumns()->diff($columns)
            : $this->manager->getColumns()->intersect($columns);

        $this->builder->select($columns->values()->toArray());
    }

    protected function buildLimitKeyword(QuerySymbol $query)
    {
        if (! ctype_digit($query->value)) {
            throw new InvalidSearchStringException('The limit must be an integer');
        }

        $this->builder->limit($query->value);
    }

    protected function buildOffsetKeyword(QuerySymbol $query)
    {
        if (! ctype_digit($query->value)) {
            throw new InvalidSearchStringException('The offset must be an integer');
        }

        $this->builder->offset($query->value);
    }
}