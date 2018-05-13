<?php

namespace Lorisleiva\LaravelSearchString\Parser;

use Lorisleiva\LaravelSearchString\Visitor\Visitor;

class QuerySymbol extends Symbol
{
    public $key;
    public $operator;
    public $value;

    function __construct($key, $operator, $value)
    {
        $this->key = $key;
        $this->operator = $operator;
        $this->value = $value;
    }

    public function accept(Visitor $visitor)
    {
        return $visitor->visitQuery($this);
    }
}