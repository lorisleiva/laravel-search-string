<?php

namespace Lorisleiva\LaravelSearchString\Visitor;

use Lorisleiva\LaravelSearchString\Parser\AndSymbol;
use Lorisleiva\LaravelSearchString\Parser\NotSymbol;
use Lorisleiva\LaravelSearchString\Parser\OrSymbol;
use Lorisleiva\LaravelSearchString\Parser\QuerySymbol;

class FlattenAndOrVisitor implements Visitor
{
    public function visitOr(OrSymbol $or)
    {
        $leaves = $or->expressions->map->accept($this)->flatMap(function ($leaf) {
            return $leaf instanceof OrSymbol ? $leaf->expressions : [$leaf];
        });

        return new OrSymbol($leaves);
    }

    public function visitAnd(AndSymbol $and)
    {
        $leaves = $and->expressions->map->accept($this)->flatMap(function ($leaf) {
            return $leaf instanceof AndSymbol ? $leaf->expressions : [$leaf];
        });

        return new AndSymbol($leaves);
    }

    public function visitNot(NotSymbol $not)
    {
        return new NotSymbol($not->expression->accept($this));
    }

    public function visitQuery(QuerySymbol $query)
    {
        return $query;
    }
}