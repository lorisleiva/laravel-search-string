<?php

namespace Lorisleiva\LaravelSearchString\Visitor;

use Lorisleiva\LaravelSearchString\Parser\AndSymbol;
use Lorisleiva\LaravelSearchString\Parser\NotSymbol;
use Lorisleiva\LaravelSearchString\Parser\NullSymbol;
use Lorisleiva\LaravelSearchString\Parser\OrSymbol;
use Lorisleiva\LaravelSearchString\Parser\QuerySymbol;
use Lorisleiva\LaravelSearchString\Parser\SearchSymbol;

abstract class ExtractSpecialQueryVisitor implements Visitor
{
    protected $key;
    protected $operator;
    protected $value;
    protected $lastSpecialQuery = null;

    public function __construct($key, $operator = null, $value = null)
    {
        $this->key = $key;
        $this->operator = $operator ?? '/.*/';
        $this->value = $value ?? '/.*/';
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
        if (! $this->isSpecial($query)) {
            return $query;
        }

        $this->useSpecialQuery($query, $this->lastSpecialQuery);
        $this->lastSpecialQuery = $query;

        return new NullSymbol;
    }

    public function visitSearch(SearchSymbol $search)
    {
        return $search;
    }

    public function visitNull(NullSymbol $null)
    {
        return $null;
    }

    protected function isSpecial($query)
    {
        $keyMatch = preg_match($this->key, $query->key);
        $operatorMatch = preg_match($this->operator, $query->operator);
        $valueMatch = collect($query->value)->every(function ($value) {
            return preg_match($this->value, $value);
        });

        return $keyMatch && $operatorMatch && $valueMatch;
    }

    abstract protected function useSpecialQuery($query, $lastQuery);
}