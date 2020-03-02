<?php

namespace Lorisleiva\LaravelSearchString\Visitor;

use Lorisleiva\LaravelSearchString\Parser\AndSymbol;
use Lorisleiva\LaravelSearchString\Parser\NotSymbol;
use Lorisleiva\LaravelSearchString\Parser\NullSymbol;
use Lorisleiva\LaravelSearchString\Parser\OrSymbol;
use Lorisleiva\LaravelSearchString\Parser\RelationSymbol;

class OptimizeAstVisitor extends Visitor
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

    public function visitRelation(RelationSymbol $relation)
    {
        if ($relation->constraints) {
            $relation->constraints = $relation->constraints->accept($this);
        }

        switch (true) {
            case $relation->operator == '>' && $relation->count == 0:
            case $relation->operator == '>=' && $relation->count <= 1:
                return new RelationSymbol($relation->relation, $relation->constraints);

            case $relation->operator == '=' && $relation->count == 0:
            case $relation->operator == '<=' && $relation->count == 0:
            case $relation->operator == '<' && $relation->count == 1:
                return new RelationSymbol($relation->relation, $relation->constraints, null, null, true);
        }

        return $relation;
    }
}