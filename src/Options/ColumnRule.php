<?php

namespace Lorisleiva\LaravelSearchString\Options;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use LogicException;
use Lorisleiva\LaravelSearchString\Concerns\SearchString;
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
            $this->setRelationshipModelAndManager($model);
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

    protected function setRelationshipModelAndManager(Model $model)
    {
        $relation = $this->getRelation($model);
        $related = $relation->getRelated();
        $this->relationshipModel = $related;

        if (! in_array(SearchString::class, class_uses_recursive($related))) {
            throw new LogicException(sprintf(
                '%s must use the %s trait to be used as a relationship.', get_class($related), SearchString::class
            ));
        }

        $this->relationshipManager = $related->getSearchStringManager();
    }

    public function getRelation(Model $model): Relation
    {
        $method = $this->column;
        $relation = $model->$method();

        if (! $relation instanceof Relation) {
            if (is_null($relation)) {
                throw new LogicException(sprintf(
                    '%s::%s must return a relationship instance, but "null" was returned. Was the "return" keyword used?', get_class($model), $method
                ));
            }

            throw new LogicException(sprintf(
                '%s::%s must return a relationship instance.', get_class($model), $method
            ));
        }

        return $relation;
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
