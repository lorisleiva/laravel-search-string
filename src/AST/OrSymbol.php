<?php

namespace Lorisleiva\LaravelSearchString\AST;

use Lorisleiva\LaravelSearchString\Visitors\Visitor;

class OrSymbol extends Symbol
{
    public $expressions;

    function __construct($expressions = [])
    {
        $this->expressions = collect($expressions);
    }

    public function accept(Visitor $visitor)
    {
        return $visitor->visitOr($this);
    }
}
