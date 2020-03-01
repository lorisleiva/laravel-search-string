<?php

namespace Lorisleiva\LaravelSearchString\Parser;

use Lorisleiva\LaravelSearchString\Visitor\Visitor;

class RelationSymbol extends Symbol
{
    public $relation;
    public $constraints;
    public $operator;
    public $value;
    public $negated;

    function __construct($relation, $constraints = null, $operator = null, $value = null, $negated = false)
    {
        $this->relation = $relation;
        $this->constraints = $constraints;
        $this->operator = $operator;
        $this->value = $value;
        $this->negated = $negated;
    }

    public function accept(Visitor $visitor)
    {
        return $visitor->visitRelation($this);
    }
}