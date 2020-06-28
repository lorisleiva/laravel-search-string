<?php

namespace Lorisleiva\LaravelSearchString\Options;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Lorisleiva\LaravelSearchString\SearchStringManager;

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

    /** @var Model|null */
    public $relationshipModel = null;

    /** @var SearchStringManager|null */
    public $relationshipManager = null;

    /** @var Collection */
    public $map;

    public function __construct(Model $model, $column, $rule = null)
    {
        parent::__construct($column, $rule);

        $isBoolean = $this->isCastAsBoolean($model, $column);
        $isDate = $this->isCastAsDate($model, $column);

        $this->date = Arr::get($rule, 'date', $isDate);
        $this->boolean = Arr::get($rule, 'boolean', $isBoolean || $isDate);
        $this->searchable = Arr::get($rule, 'searchable', false);
        $this->relationship = Arr::get($rule, 'relationship', false);
        $this->map = Collection::wrap(Arr::get($rule, 'map', []));

        if ($this->relationship) {
            $this->fetchRelationshipModelAndManager();
        }
    }

    protected function isCastAsDate(Model $model, string $column): bool
    {
        return $model->hasCast($column, ['date', 'datetime'])
            || in_array($column, $model->getDates());
    }

    protected function isCastAsBoolean(Model $model, string $column): bool
    {
        return $model->hasCast($column, 'boolean');
    }

    protected function fetchRelationshipModelAndManager()
    {
        $this->column; // TODO
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
