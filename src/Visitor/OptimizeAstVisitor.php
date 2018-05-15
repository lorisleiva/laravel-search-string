<?php

namespace Lorisleiva\LaravelSearchString\Visitor;

use Lorisleiva\LaravelSearchString\Parser\AndSymbol;
use Lorisleiva\LaravelSearchString\Parser\NotSymbol;
use Lorisleiva\LaravelSearchString\Parser\NullSymbol;
use Lorisleiva\LaravelSearchString\Parser\OrSymbol;
use Lorisleiva\LaravelSearchString\Parser\QuerySymbol;

class OptimizeAstVisitor implements Visitor
{
    public function visitOr(OrSymbol $or)
    {
        $leaves = $or->expressions->map->accept($this)->flatMap(function ($leaf) {
            if ($leaf instanceof OrSymbol) {
                return $leaf->expressions;
            }
            return $leaf instanceof NullSymbol ? [] : [$leaf];
        });

        if ($leaves->isEmpty()) return new NullSymbol;
        if ($leaves->count() === 1) return $leaves->first();
        return new OrSymbol($leaves);
    }

    public function visitAnd(AndSymbol $and)
    {
        $leaves = $and->expressions->map->accept($this)->flatMap(function ($leaf) {
            if ($leaf instanceof AndSymbol) {
                return $leaf->expressions;
            }
            return $leaf instanceof NullSymbol ? [] : [$leaf];
        });

        if ($leaves->isEmpty()) return new NullSymbol;
        if ($leaves->count() === 1) return $leaves->first();
        return new AndSymbol($leaves);
    }

    public function visitNot(NotSymbol $not)
    {
        $leaf = $not->expression->accept($this);
        return $leaf instanceof NullSymbol ? new NullSymbol : new NotSymbol($leaf);
    }

    public function visitQuery(QuerySymbol $query)
    {
        return $query;
    }

    public function visitNull(NullSymbol $null)
    {
        return $null;
    }
}