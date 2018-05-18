<?php

namespace Lorisleiva\LaravelSearchString\Visitor;

use Illuminate\Database\Eloquent\Builder;
use Lorisleiva\LaravelSearchString\Exceptions\InvalidSearchStringException;
use Lorisleiva\LaravelSearchString\Parser\QuerySymbol;

class BuildKeywordsVisitor extends Visitor
{
    protected $manager;
    protected $builder;
    protected $lastMatchedQuery = null;

    public function __construct($manager, $builder)
    {
        $this->manager = $manager;
        $this->builder = $builder;
    }

    public function visitQuery(QuerySymbol $query)
    {
        if ($rule = $this->manager->getRuleForQuery($query, 'keywords')) {
            $this->buildKeyword($rule->column, $query, $this->lastMatchedQuery);
            $this->lastMatchedQuery = $query;
        }
        
        return $query;
    }

    public function buildKeyword($keyword, $query, $lastQuery)
    {
        $methodName = 'build' . title_case(camel_case($keyword)) . 'Keyword';

        return $this->$methodName($this->builder, $query, $lastQuery);
    }

    protected function buildOrderByKeyword(Builder $builder, QuerySymbol $query, $lastQuery)
    {
        $builder->getQuery()->orders = null;

        collect($query->value)->each(function ($value) use ($builder) {
            $desc = starts_with($value, '-') ? 'desc' : 'asc';
            $column = starts_with($value, '-') ? str_after($value, '-') : $value;
            $builder->orderBy($column, $desc);
        });
    }

    protected function buildSelectKeyword(Builder $builder, QuerySymbol $query, $lastQuery)
    {
        $columns = array_wrap($query->value);

        $columns = in_array($query->operator, ['!=', 'not in'])
            ? $this->manager->getColumns()->diff($columns)
            : $this->manager->getColumns()->intersect($columns);

        $builder->select($columns->values()->toArray());
    }

    protected function buildLimitKeyword(Builder $builder, QuerySymbol $query, $lastQuery)
    {
        if (! ctype_digit($query->value)) {
            throw new InvalidSearchStringException('The limit must be an integer');
        }

        $builder->limit($query->value);
    }

    protected function buildOffsetKeyword(Builder $builder, QuerySymbol $query, $lastQuery)
    {
        if (! ctype_digit($query->value)) {
            throw new InvalidSearchStringException('The offset must be an integer');
        }

        $builder->offset($query->value);
    }
}