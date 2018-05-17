<?php

namespace Lorisleiva\LaravelSearchString\Options;

use Lorisleiva\LaravelSearchString\Options\Rule;
use Lorisleiva\LaravelSearchString\Parser\QuerySymbol;

class ColumnRule extends Rule
{
    public $searchable = false;
    public $date = false;
    public $boolean = false;

    public function __construct($column, $rule = null, $isDate = false, $isBoolean = false)
    {
        parent::__construct($column, $rule);

        $this->boolean = array_get($rule, 'boolean', $isBoolean || $isDate);
        $this->date = array_get($rule, 'date', $isDate);
        $this->searchable = array_get($rule, 'searchable', false);
    }

    public function __toString()
    {
        $parent = parent::__toString();
        $booleans = collect([
            $this->searchable ? 'searchable' : null,
            $this->boolean ? 'boolean' : null,
            $this->date ? 'date' : null,
        ])->filter()->implode('][');

        return "{$parent}[$booleans]";
    }
}