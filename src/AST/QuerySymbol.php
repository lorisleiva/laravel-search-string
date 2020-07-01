<?php

namespace Lorisleiva\LaravelSearchString\AST;

use Illuminate\Support\Arr;
use Lorisleiva\LaravelSearchString\Visitors\Visitor;

class QuerySymbol extends Symbol
{
    use CanHaveRule;

    /** @var string */
    public $key;

    /** @var string */
    public $operator;

    /** @var mixed */
    public $value;

    function __construct(string $key, string $operator, $value)
    {
        $this->key = $key;
        $this->operator = $operator;
        $this->value = $value;
    }

    public function accept(Visitor $visitor)
    {
        return $visitor->visitQuery($this);
    }

    public function negate()
    {
        if (is_bool($this->value)) {
            $this->value = ! $this->value;
        } else {
            $this->operator = $this->getReverseOperator();
        }

        return $this;
    }

    protected function getReverseOperator()
    {
        return Arr::get([
            '=' => '!=',
            '!=' => '=',
            '>' => '<=',
            '>=' => '<',
            '<' => '>=',
            '<=' => '>',
        ], $this->operator, $this->operator);
    }
}
