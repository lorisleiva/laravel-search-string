<?php

namespace Lorisleiva\LaravelSearchString\Visitor;

use Lorisleiva\LaravelSearchString\Parser\AndSymbol;
use Lorisleiva\LaravelSearchString\Parser\NotSymbol;
use Lorisleiva\LaravelSearchString\Parser\NullSymbol;
use Lorisleiva\LaravelSearchString\Parser\OrSymbol;
use Lorisleiva\LaravelSearchString\Parser\QuerySymbol;

class DumpVisitor implements Visitor
{
    protected $indent = 0;

    public function indent()
    {
        if ($this->indent === 0) return '';
        return str_repeat(' > ', $this->indent) . ' ';
    }

    public function dump($value)
    {
        return $this->indent() . $value . "\n";
    }

    public function visitOr(OrSymbol $or)
    {
        $root = $this->dump('OR');
        $this->indent++;
        $leaves = collect($or->expressions)->map->accept($this)->implode('');
        $this->indent--;
        return $root . $leaves;
    }

    public function visitAnd(AndSymbol $and)
    {
        $root = $this->dump('AND');
        $this->indent++;
        $leaves = collect($and->expressions)->map->accept($this)->implode('');
        $this->indent--;
        return $root . $leaves;
    }

    public function visitNot(NotSymbol $not)
    {
        $root = $this->dump('NOT');
        $this->indent++;
        $leaves = $not->expression->accept($this);
        $this->indent--;
        return $root . $leaves;
    }

    public function visitQuery(QuerySymbol $query)
    {
        return $this->dump("$query->key $query->operator $query->value");
    }

    public function visitNull(NullSymbol $null)
    {
        return $this->dump('NULL');
    }
}