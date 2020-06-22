<?php

namespace Lorisleiva\LaravelSearchString\AST;

use Lorisleiva\LaravelSearchString\Visitors\Visitor;

class AndSymbol extends Symbol
{
    public $expressions;

    function __construct($expressions = [])
    {
        $this->expressions = collect($expressions);
    }

    public function accept(Visitor $visitor)
    {
        return $visitor->visitAnd($this);
    }
}
