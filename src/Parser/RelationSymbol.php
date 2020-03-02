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

    function __construct($relation, $constraints, $operator = null, $value = null, $negated = false)
    {
        if (strpos($relation, '.') !== false) {
            $relations = explode('.', $relation);
            $deepestRelation = array_pop($relations);
            $relation = array_shift($relations);

            $relations = array_reverse($relations);

            $symbol = new static($deepestRelation, $constraints);

            foreach ($relations as $nestedRelation) {
                $symbol = new static($nestedRelation, $symbol);
            }

            $constraints = $symbol;
        }

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