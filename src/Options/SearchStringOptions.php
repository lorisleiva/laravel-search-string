<?php

namespace Lorisleiva\LaravelSearchString\Options;

use Illuminate\Support\Arr;
use Lorisleiva\LaravelSearchString\Options\ColumnRule;
use Lorisleiva\LaravelSearchString\Parser\QuerySymbol;
use Lorisleiva\LaravelSearchString\Parser\RelationSymbol;

trait SearchStringOptions
{
    protected $options = [];
    protected static $fallbackOptions = [
        'columns' => [],
        'relations' => [],
        'keywords' => [
            'order_by' => 'sort',
            'select' => 'fields',
            'limit' => 'limit',
            'offset' => 'from',
        ],
    ];

    /**
     * @param $model
     */
    protected function generateOptions($model)
    {
        $options = array_replace_recursive(
            static::$fallbackOptions,
            Arr::get(config('search-string'), 'default', []),
            Arr::get(config('search-string'), get_class($model), []),
            $model->getSearchStringOptions() ?? []
        );

        $this->options = $this->parseOptions($options, $model);
    }

    protected function parseOptions($options, $model)
    {
        return collect([
            'columns' => $this->parseColumns($options, $model),
            'relations' => $this->parseRelations($options, $model),
            'keywords' => $this->parseKeywords($options),
        ]);
    }

    protected function parseColumns($options, $model)
    {
        return collect(Arr::get($options, 'columns', []))
            ->mapWithKeys(function ($rule, $column) {
                return $this->resolveLonelyColumn($rule, $column);
            })
            ->map(function ($rule, $column) use ($model) {
                $isDate = $this->castAsDate($model, $column);
                $isBoolean = $this->castAsBoolean($model, $column);
                return new ColumnRule($column, $rule, $isDate, $isBoolean);
            });
    }

    protected function parseRelations($options, $model)
    {
        return collect(Arr::get($options, 'relations', []))
            ->mapWithKeys(function ($rule, $relation) {
                return $this->resolveLonelyColumn($rule, $relation);
            })
            ->map(function ($rule, $relation) {
                return new RelationRule($relation, $rule);
            });
    }

    protected function parseKeywords($options)
    {
        return collect(Arr::get($options, 'keywords', []))
            ->mapWithKeys(function ($rule, $keyword) {
                return $this->resolveLonelyColumn($rule, $keyword);
            })
            ->map(function ($rule, $keyword) {
                return new KeywordRule($keyword, $rule);
            });
    }

    protected function resolveLonelyColumn($rule, $column)
    {
        return is_string($column) ? [$column => $rule] : [$rule => null];
    }

    protected function castAsDate($model, $column)
    {
        return $model->hasCast($column, ['date', 'datetime'])
            || in_array($column, $model->getDates());
    }

    protected function castAsBoolean($model, $column)
    {
        return $model->hasCast($column, 'boolean');
    }

    /**
     * Helpers
     */

    public function getOptions()
    {
        return $this->options;
    }

    public function getOption($key, $default = null)
    {
        return Arr::get($this->getOptions(), $key, $default);
    }

    public function getRule($key, $operator = null, $value = null, $type = 'columns')
    {
        return $this->getOption($type)->first(function ($rule) use ($key, $operator, $value) {
            return $rule->match($key, $operator, $value);
        });
    }

    public function getRuleForQuery(QuerySymbol $query, $type = 'columns')
    {
        return $this->getRule($query->key, $query->operator, $query->value, $type);
    }

    public function getRuleForRelation(RelationSymbol $relation)
    {
        return $this->getOption('relations')->first(function ($rule) use ($relation) {
            return $rule->match($relation->relation, (bool) $relation->constraints, (bool) $relation->operator);
        });
    }

    public function getColumns()
    {
        return $this->getOption('columns')->keys();
    }

    public function getSearchables()
    {
        return $this->getOption('columns')->filter->searchable->keys();
    }
}