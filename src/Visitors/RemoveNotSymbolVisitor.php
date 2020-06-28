<?php

namespace Lorisleiva\LaravelSearchString\Visitors;

use Lorisleiva\LaravelSearchString\AST\AndSymbol;
use Lorisleiva\LaravelSearchString\AST\ListSymbol;
use Lorisleiva\LaravelSearchString\AST\NotSymbol;
use Lorisleiva\LaravelSearchString\AST\OrSymbol;
use Lorisleiva\LaravelSearchString\AST\QuerySymbol;
use Lorisleiva\LaravelSearchString\AST\SoloSymbol;

class RemoveNotSymbolVisitor extends Visitor
{
    protected $negate = false;

    public function visitOr(OrSymbol $or)
    {
        $leaves = $or->expressions->map->accept($this);
        return $this->negate ? new AndSymbol($leaves) : new OrSymbol($leaves);
    }

    public function visitAnd(AndSymbol $and)
    {
        $leaves = $and->expressions->map->accept($this);
        return $this->negate ? new OrSymbol($leaves) : new AndSymbol($leaves);
    }

    public function visitNot(NotSymbol $not)
    {
        if ($this->negate) {
            $this->negate = false;
            return $not->expression;
        }

        $this->negate = true;
        $newExpression = $not->expression->accept($this);
        $this->negate = false;
        return $newExpression;
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

    public function visitSolo(SoloSymbol $solo)
    {
        if ($this->negate) {
            $solo->negate();
        }

        return $solo;
    }
}
