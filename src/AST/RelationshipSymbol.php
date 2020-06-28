<?php

namespace Lorisleiva\LaravelSearchString\AST;

use Lorisleiva\LaravelSearchString\Visitors\Visitor;

class RelationshipSymbol extends Symbol
{
    use CanHaveRule;
    use CanBeNegated;

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
}
