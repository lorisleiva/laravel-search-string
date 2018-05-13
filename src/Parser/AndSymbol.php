<?php

namespace Lorisleiva\LaravelSearchString\Parser;

use Lorisleiva\LaravelSearchString\Visitor\Visitor;

class AndSymbol extends Symbol
{
    public $expressions;

    function __construct($expressions)
    {
        $this->expressions = $expressions;
    }

    public function accept(Visitor $visitor)
    {
        return $visitor->visitAnd($this);
    }
}