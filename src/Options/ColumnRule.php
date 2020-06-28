<?php

namespace Lorisleiva\LaravelSearchString\Options;

use Illuminate\Support\Arr;
use Illuminate\Support\Collection;

class ColumnRule extends Rule
{

    /** @var bool */
    public $date = false;

    /** @var bool */
    public $boolean = false;

    /** @var bool */
    public $searchable = false;

    /** @var bool */
    public $relationship = false;

    /** @var Collection */
    public $map;

    public function __construct($column, $rule = null, $isDate = false, $isBoolean = false)
    {
        parent::__construct($column, $rule);

        $this->date = Arr::get($rule, 'date', $isDate);
        $this->boolean = Arr::get($rule, 'boolean', $isBoolean || $isDate);
        $this->searchable = Arr::get($rule, 'searchable', false);
        $this->relationship = Arr::get($rule, 'relationship', false);
        $this->map = Collection::wrap(Arr::get($rule, 'map', []));
    }

    public function __toString()
    {
        $parent = parent::__toString();
        $booleans = collect([
            $this->searchable ? 'searchable' : null,
            $this->boolean ? 'boolean' : null,
            $this->date ? 'date' : null,
            $this->relationship ? 'relationship' : null,
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
