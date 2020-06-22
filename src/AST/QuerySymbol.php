<?php

namespace Lorisleiva\LaravelSearchString\AST;

use Lorisleiva\LaravelSearchString\Visitors\Visitor;

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
