<?php

namespace Lorisleiva\LaravelSearchString\Visitor;

use Illuminate\Database\Eloquent\Builder;
use Lorisleiva\LaravelSearchString\Parser\AndSymbol;
use Lorisleiva\LaravelSearchString\Parser\NotSymbol;
use Lorisleiva\LaravelSearchString\Parser\NullSymbol;
use Lorisleiva\LaravelSearchString\Parser\OrSymbol;
use Lorisleiva\LaravelSearchString\Parser\QuerySymbol;
use Lorisleiva\LaravelSearchString\Parser\SoloSymbol;

class RemoveKeywordsVisitor implements Visitor
{
    protected $manager;
    protected $lastMatchedQuery = null;

    public function __construct($manager)
    {
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
        return $this->manager->getRuleForQuery($query, 'keywords')
            ? new NullSymbol
            : $query;
    }

    public function visitSolo(SoloSymbol $solo)
    {
        return $solo;
    }

    public function visitNull(NullSymbol $null)
    {
        return $null;
    }
}