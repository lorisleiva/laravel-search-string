<?php

namespace Lorisleiva\LaravelSearchString\Options;

use Illuminate\Support\Arr;
use Illuminate\Support\Collection;

class ColumnRule extends Rule
{
    public $searchable = false;
    public $date = false;
    public $boolean = false;
    public $map = [];

    public function __construct($column, $rule = null, $isDate = false, $isBoolean = false)
    {
        parent::__construct($column, $rule);

        $this->boolean = Arr::get($rule, 'boolean', $isBoolean || $isDate);
        $this->date = Arr::get($rule, 'date', $isDate);
        $this->searchable = Arr::get($rule, 'searchable', false);
        $this->map = Collection::wrap(Arr::get($rule, 'map', []));
    }

    public function __toString()
    {
        $parent = parent::__toString();
        $booleans = collect([
            $this->searchable ? 'searchable' : null,
            $this->boolean ? 'boolean' : null,
            $this->date ? 'date' : null,
        ])->filter()->implode('][');

        $mappings = $this->map->map(function ($value, $key) {
            return "{$key}={$value}";
        })->implode(',');

        if ($mappings !== '') {
            $mappings = "[{$mappings}]";
        }

        return "{$parent}[{$booleans}]$mappings";
    }
}
