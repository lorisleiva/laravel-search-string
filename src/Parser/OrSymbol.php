<?php

namespace Lorisleiva\LaravelSearchString\Parser;

use Lorisleiva\LaravelSearchString\Visitor\Visitor;

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