<?php

namespace Lorisleiva\LaravelSearchString\Parser;

use Lorisleiva\LaravelSearchString\Visitor\Visitor;

class SearchSymbol extends Symbol
{
    public $content;
    public $exclude;

    function __construct($content, $exclude = false)
    {
        $this->content = $content;
        $this->exclude = $exclude;
    }

    public function accept(Visitor $visitor)
    {
        return $visitor->visitSearch($this);
    }
}