<?php

namespace Lorisleiva\LaravelSearchString\AST;

use Lorisleiva\LaravelSearchString\Visitors\Visitor;

class NullSymbol extends Symbol
{
    public function accept(Visitor $visitor)
    {
        return $visitor->visitNull($this);
    }
}
