<?php

namespace Lorisleiva\LaravelSearchString\Visitors;

use Lorisleiva\LaravelSearchString\AST\AndSymbol;
use Lorisleiva\LaravelSearchString\AST\NotSymbol;
use Lorisleiva\LaravelSearchString\AST\EmptySymbol;
use Lorisleiva\LaravelSearchString\AST\OrSymbol;
use Lorisleiva\LaravelSearchString\AST\QuerySymbol;
use Lorisleiva\LaravelSearchString\AST\SoloSymbol;

class InlineDumpVisitor extends Visitor
{
    protected $shortenQuery;

    public function __construct($shortenQuery = false)
    {
        $this->shortenQuery = $shortenQuery;
    }

    public function visitOr(OrSymbol $or)
    {
        return 'OR(' . collect($or->expressions)->map->accept($this)->implode(', ') . ')';
    }

    public function visitAnd(AndSymbol $and)
    {
        return 'AND(' . collect($and->expressions)->map->accept($this)->implode(', ') . ')';
    }

    public function visitNot(NotSymbol $not)
    {
        return 'NOT(' . $not->expression->accept($this) . ')';
    }

    public function visitQuery(QuerySymbol $query)
    {
        $value = $query->value;

        if ($this->shortenQuery) {
            $value = is_array($value) ? '[' . implode(', ', $value) . ']' : $value;
            return $query->key . (is_bool($value) ? '' : "$query->operator $value");
        }

        $value = is_bool($value) ? ($value ? 'true' : 'false') : $value;
        $value = is_array($value) ? '[' . implode(', ', $value) . ']' : $value;
        return "QUERY($query->key $query->operator $value)";
    }

    public function visitSolo(SoloSymbol $solo)
    {
        if ($this->shortenQuery) {
            return $solo->content;
        }

        return $solo->negated
            ? "SOLO_NOT($solo->content)"
            : "SOLO($solo->content)";
    }

    public function visitEmpty(EmptySymbol $empty)
    {
        return 'EMPTY';
    }
}
