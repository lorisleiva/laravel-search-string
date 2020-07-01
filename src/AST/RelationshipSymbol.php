<?php

namespace Lorisleiva\LaravelSearchString\AST;

use Illuminate\Support\Arr;
use Lorisleiva\LaravelSearchString\Visitors\Visitor;

class RelationshipSymbol extends Symbol
{
    use CanHaveRule;

    /** @var string */
    public $key;

    /** @var Symbol */
    public $expression;

    /** @var string */
    public $expectedOperator;

    /** @var int */
    public $expectedCount;

    function __construct(string $key, Symbol $expression, string $expectedOperator = '>', int $expectedCount = 0)
    {
        $this->key = $key;
        $this->expression = $expression;
        $this->expectedOperator = $expectedOperator;
        $this->expectedCount = $expectedCount;
    }

    public function accept(Visitor $visitor)
    {
        return $visitor->visitRelationship($this);
    }

    public function negate()
    {
        $this->expectedOperator = $this->getReverseOperator();

        return $this;
    }

    public function expectedOperation(): string
    {
        return sprintf('%s %s', $this->expectedOperator, $this->expectedCount);
    }

    public function isCheckingInexistance(): bool
    {
        return in_array($this->expectedOperation(), ['<= 0', '= 0', '< 1']);
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
        ], $this->expectedOperator, $this->expectedOperator);
    }
}
