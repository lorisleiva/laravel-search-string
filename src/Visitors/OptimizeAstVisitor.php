<?php

namespace Lorisleiva\LaravelSearchString\Visitors;

use Lorisleiva\LaravelSearchString\AST\AndSymbol;
use Lorisleiva\LaravelSearchString\AST\NotSymbol;
use Lorisleiva\LaravelSearchString\AST\EmptySymbol;
use Lorisleiva\LaravelSearchString\AST\OrSymbol;

class OptimizeAstVisitor extends Visitor
{
    // TODO: Optimize relationships by grouping them together when applicable.

    public function visitOr(OrSymbol $or)
    {
        $leaves = $or->expressions->map->accept($this)->flatMap(function ($leaf) {
            if ($leaf instanceof OrSymbol) {
                return $leaf->expressions;
            }
            return $leaf instanceof EmptySymbol ? [] : [$leaf];
        });

        if ($leaves->isEmpty()) return new EmptySymbol;
        if ($leaves->count() === 1) return $leaves->first();
        return new OrSymbol($leaves);
    }

    public function visitAnd(AndSymbol $and)
    {
        $leaves = $and->expressions->map->accept($this)->flatMap(function ($leaf) {
            if ($leaf instanceof AndSymbol) {
                return $leaf->expressions;
            }
            return $leaf instanceof EmptySymbol ? [] : [$leaf];
        });

        if ($leaves->isEmpty()) return new EmptySymbol;
        if ($leaves->count() === 1) return $leaves->first();
        return new AndSymbol($leaves);
    }

    public function visitNot(NotSymbol $not)
    {
        $leaf = $not->expression->accept($this);
        return $leaf instanceof EmptySymbol ? new EmptySymbol : new NotSymbol($leaf);
    }
}
