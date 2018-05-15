<?php

namespace Lorisleiva\LaravelSearchString\Parser;

use Lorisleiva\LaravelSearchString\Visitor\Visitor;

class NullSymbol extends Symbol
{
    public function accept(Visitor $visitor)
    {
        return $visitor->visitNull($this);
    }
}