<?php

namespace Lorisleiva\LaravelSearchString\Visitor;

use Lorisleiva\LaravelSearchString\Parser\AndSymbol;
use Lorisleiva\LaravelSearchString\Parser\NotSymbol;
use Lorisleiva\LaravelSearchString\Parser\OrSymbol;
use Lorisleiva\LaravelSearchString\Parser\QuerySymbol;

class InlineDumpVisitor implements Visitor
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
}