<?php

namespace Lorisleiva\LaravelSearchString\Visitors;

use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Lorisleiva\LaravelSearchString\Exceptions\InvalidSearchStringException;
use Lorisleiva\LaravelSearchString\AST\QuerySymbol;

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
        $methodName = 'build' . Str::title(Str::camel($keyword)) . 'Keyword';

        return $this->$methodName($query);
    }

    protected function buildOrderByKeyword(QuerySymbol $query)
    {
        $this->builder->getQuery()->orders = null;

        collect($query->value)->each(function ($value) {
            $desc = Str::startsWith($value, '-') ? 'desc' : 'asc';
            $column = Str::startsWith($value, '-') ? Str::after($value, '-') : $value;
            $this->builder->orderBy($column, $desc);
        });
    }

    protected function buildSelectKeyword(QuerySymbol $query)
    {
        $columns = Arr::wrap($query->value);

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
