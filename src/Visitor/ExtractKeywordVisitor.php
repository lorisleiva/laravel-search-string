<?php

namespace Lorisleiva\LaravelSearchString\Visitor;

use Lorisleiva\LaravelSearchString\Parser\AndSymbol;
use Lorisleiva\LaravelSearchString\Parser\NotSymbol;
use Lorisleiva\LaravelSearchString\Parser\NullSymbol;
use Lorisleiva\LaravelSearchString\Parser\OrSymbol;
use Lorisleiva\LaravelSearchString\Parser\QuerySymbol;
use Lorisleiva\LaravelSearchString\Parser\SearchSymbol;

class ExtractKeywordVisitor implements Visitor
{
    protected $builder;
    protected $manager;
    protected $keyword;
    protected $lastMatchedQuery = null;

    public function __construct($builder, $manager, $keyword)
    {
        $this->builder = $builder;
        $this->manager = $manager;
        $this->keyword = $keyword;
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
        if (! $this->getKeywordRule()->matchQuery($query)) {
            return $query;
        }

        $this->resolveKeyword($query, $this->lastMatchedQuery);
        $this->lastMatchedQuery = $query;

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

    public function getKeywordRule()
    {
        return $this->manager->getKeywordRule($this->keyword);
    }

    public function resolveKeyword($query, $lastQuery)
    {
        $methodName = 'resolve' . title_case(camel_case($this->keyword)) . 'Keyword';

        return $this->manager->$methodName($this->builder, $query, $lastQuery);
    }
}