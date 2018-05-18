<?php

namespace Lorisleiva\LaravelSearchString\Parser;

use Lorisleiva\LaravelSearchString\Visitor\Visitor;

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