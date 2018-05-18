<?php

namespace Lorisleiva\LaravelSearchString\Visitor;

use Illuminate\Database\Eloquent\Builder;
use Lorisleiva\LaravelSearchString\Exceptions\InvalidSearchStringException;
use Lorisleiva\LaravelSearchString\Parser\AndSymbol;
use Lorisleiva\LaravelSearchString\Parser\NotSymbol;
use Lorisleiva\LaravelSearchString\Parser\NullSymbol;
use Lorisleiva\LaravelSearchString\Parser\OrSymbol;
use Lorisleiva\LaravelSearchString\Parser\QuerySymbol;
use Lorisleiva\LaravelSearchString\Parser\SoloSymbol;

class ResolveKeywordsVisitor implements Visitor
{
    protected $builder;
    protected $manager;
    protected $lastMatchedQuery = null;

    public function __construct($builder, $manager)
    {
        $this->builder = $builder;
        $this->manager = $manager;
    }

    public function visitOr(OrSymbol $or)
    {
        return new OrSymbol($or->expressions->map->accept($this));
    }

    public function visitAnd(AndSymbol $and)
    {
        return new AndSymbol($and->expressions->map->accept($this));
    }

    public function visitNot(NotSymbol $not)
    {
        return new NotSymbol($not->expression->accept($this));
    }

    public function visitQuery(QuerySymbol $query)
    {
        if ($rule = $this->manager->getRuleForQuery($query, 'keywords')) {
            $this->resolveKeyword($rule->column, $query, $this->lastMatchedQuery);
            $this->lastMatchedQuery = $query;
        }
        
        return $query;
    }

    public function visitSolo(SoloSymbol $solo)
    {
        return $solo;
    }

    public function visitNull(NullSymbol $null)
    {
        return $null;
    }

    public function resolveKeyword($keyword, $query, $lastQuery)
    {
        $methodName = 'resolve' . title_case(camel_case($keyword)) . 'Keyword';

        return $this->$methodName($this->builder, $query, $lastQuery);
    }

    protected function resolveOrderByKeyword(Builder $builder, QuerySymbol $query, $lastQuery)
    {
        $builder->getQuery()->orders = null;

        collect($query->value)->each(function ($value) use ($builder) {
            $desc = starts_with($value, '-') ? 'desc' : 'asc';
            $column = starts_with($value, '-') ? str_after($value, '-') : $value;
            $builder->orderBy($column, $desc);
        });
    }

    protected function resolveSelectKeyword(Builder $builder, QuerySymbol $query, $lastQuery)
    {
        $columns = array_wrap($query->value);

        $columns = in_array($query->operator, ['!=', 'not in'])
            ? $this->manager->getColumns()->diff($columns)
            : $this->manager->getColumns()->intersect($columns);

        $builder->select($columns->values()->toArray());
    }

    protected function resolveLimitKeyword(Builder $builder, QuerySymbol $query, $lastQuery)
    {
        if (! ctype_digit($query->value)) {
            throw new InvalidSearchStringException('The limit must be an integer');
        }

        $builder->limit($query->value);
    }

    protected function resolveOffsetKeyword(Builder $builder, QuerySymbol $query, $lastQuery)
    {
        if (! ctype_digit($query->value)) {
            throw new InvalidSearchStringException('The offset must be an integer');
        }

        $builder->offset($query->value);
    }
}