<?php

namespace Lorisleiva\LaravelSearchString\Options;

use Illuminate\Support\Arr;
use Lorisleiva\LaravelSearchString\Options\Rule;
use Lorisleiva\LaravelSearchString\Parser\RelationSymbol;

class RelationRule extends Rule
{
    public $queryable = true;
    public $countable = true;

    public function __construct($relation, $rule = null)
    {
        parent::__construct($relation, $rule);

        $this->queryable = Arr::get($rule, 'queryable', true);
        $this->countable = Arr::get($rule, 'countable', true);
    }

    public function match($relation, $hasQuery = true, $hasCount = true)
    {
        return preg_match($this->key, $relation);
    }

    public function matchRelation(RelationSymbol $relation)
    {
        return $this->match($relation->relation, (bool) $relation->constraints, (bool) $relation->operator);
    }

    public function __toString()
    {
        $booleans = collect([
            $this->queryable ? 'queryable' : null,
            $this->countable ? 'countable' : null,
        ])->filter()->implode('][');

        return "[$this->key][$booleans]";
    }
}