<?php

namespace Lorisleiva\LaravelSearchString\Visitors;

use Lorisleiva\LaravelSearchString\AST\AndSymbol;
use Lorisleiva\LaravelSearchString\AST\ListSymbol;
use Lorisleiva\LaravelSearchString\AST\NotSymbol;
use Lorisleiva\LaravelSearchString\AST\OrSymbol;
use Lorisleiva\LaravelSearchString\AST\QuerySymbol;
use Lorisleiva\LaravelSearchString\AST\RelationshipSymbol;
use Lorisleiva\LaravelSearchString\AST\SoloSymbol;

class RemoveNotSymbolVisitor extends Visitor
{
    protected $negate = false;

    public function visitOr(OrSymbol $or)
    {
        $leaves = $this->spreadNegationState(function () use ($or) {
            return $or->expressions->map->accept($this);
        });

        return $this->negate ? new AndSymbol($leaves) : new OrSymbol($leaves);
    }

    public function visitAnd(AndSymbol $and)
    {
        $leaves = $this->spreadNegationState(function () use ($and) {
            return $and->expressions->map->accept($this);
        });

        return $this->negate ? new OrSymbol($leaves) : new AndSymbol($leaves);
    }

    public function visitNot(NotSymbol $not)
    {
        $this->negate = ! $this->negate;
        $expression = $not->expression->accept($this);
        $this->negate = false;

        return $expression;
    }

    public function visitRelationship(RelationshipSymbol $relationship)
    {
        $relationship->expression = $this->resetNegationState(function () use ($relationship) {
            return $relationship->expression->accept($this);
        });

        if ($this->negate) {
            $relationship->negate();
        }

        return $relationship;
    }

    public function visitSolo(SoloSymbol $solo)
    {
        if ($this->negate) {
            $solo->negate();
        }

        return $solo;
    }

    public function visitQuery(QuerySymbol $query)
    {
        if ($this->negate) {
            $query->negate();
        }

        return $query;
    }

    public function visitList(ListSymbol $list)
    {
        if ($this->negate) {
            $list->negate();
        }

        return $list;
    }

    protected function localNegationState(bool $value, callable $callback)
    {
        $originalNegate = $this->negate;
        $this->negate = $value;
        $result = $callback();
        $this->negate = $originalNegate;

        return $result;
    }

    public function resetNegationState(callable $callback)
    {
        return $this->localNegationState(false, $callback);
    }

    public function spreadNegationState(callable $callback)
    {
        return $this->localNegationState($this->negate, $callback);
    }
}
