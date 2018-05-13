<?php

namespace Lorisleiva\LaravelSearchString\Parser;

use Lorisleiva\LaravelSearchString\Visitor\Visitor;

class NotSymbol extends Symbol
{
    public $expression;

    function __construct($expression)
    {
        $this->expression = $expression;
    }

    public function accept(Visitor $visitor)
    {
        return $visitor->visitNot($this);
    }
}