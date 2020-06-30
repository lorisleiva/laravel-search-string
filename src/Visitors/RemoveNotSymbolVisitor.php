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
        $originalNegate = $this->negate;

        $leaves = $or->expressions->map(function ($expression) use ($originalNegate) {
            $this->negate = $originalNegate;
            return $expression->accept($this);
        });

        $this->negate = $originalNegate;

        return $this->negate ? new AndSymbol($leaves) : new OrSymbol($leaves);
    }

    public function visitAnd(AndSymbol $and)
    {
        $originalNegate = $this->negate;

        $leaves = $and->expressions->map(function ($expression) use ($originalNegate) {
            $this->negate = $originalNegate;
            return $expression->accept($this);
        });

        $this->negate = $originalNegate;

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
        $originalNegate = $this->negate;
        $this->negate = false;
        $relationship->expression = $relationship->expression->accept($this);
        $this->negate = $originalNegate;

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
}
