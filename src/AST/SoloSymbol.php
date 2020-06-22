<?php

namespace Lorisleiva\LaravelSearchString\AST;

use Lorisleiva\LaravelSearchString\Visitors\Visitor;

class SoloSymbol extends Symbol
{
    public $content;
    public $negated;

    function __construct($content, $negated = false)
    {
        $this->content = $content;
        $this->negated = $negated;
    }

    public function accept(Visitor $visitor)
    {
        return $visitor->visitSolo($this);
    }
}
