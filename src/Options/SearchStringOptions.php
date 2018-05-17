<?php

namespace Lorisleiva\LaravelSearchString\Options;

use Lorisleiva\LaravelSearchString\Options\ColumnRule;
use Lorisleiva\LaravelSearchString\Parser\QuerySymbol;

trait SearchStringOptions
{
    protected $options = [];
    protected static $fallbackOptions = [
        'columns' => [],
        'keywords' => [
            'order_by' => 'sort',
            'select' => 'fields',
            'limit' => 'limit',
            'offset' => 'from',
        ],
    ];

    protected function generateOptions($model)
    {
        $options = array_replace_recursive(
            static::$fallbackOptions,
            array_get(config('search-string'), 'default', []),
            array_get(config('search-string'), get_class($model), []),
            $model->getSearchStringOptions() ?? []
        );

        $this->options = $this->parseOptions($options, $model);
    }
    
    protected function parseOptions($options, $model)
    {
        return collect([
            'columns' => $this->parseColumns($options, $model),
            'keywords' => $this->parseKeywords($options),
        ]);
    }

    protected function parseColumns($options, $model)
    {
        return collect(array_get($options, 'columns', []))
            ->mapWithKeys(function ($rule, $column) {
                return $this->resolveLonelyColumn($rule, $column);
            })
            ->map(function ($rule, $column) use ($model) {
                $isDate = $this->castAsDate($model, $column);
                $isBoolean = $this->castAsBoolean($model, $column);
                return new ColumnRule($column, $rule, $isDate, $isBoolean);
            });
    }

    protected function parseKeywords($options)
    {
        return collect(array_get($options, 'keywords', []))
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
        return array_get($this->getOptions(), $key, $default);
    }

    public function getColumnRule($key, $operator = null, $value = null)
    {
        return $this->getOption('columns')->first(function ($rule) use ($key, $operator, $value) {
            return $rule->match($key, $operator, $value);
        });
    }

    public function getColumnRuleForQuery(QuerySymbol $query)
    {
        return $this->getColumnRule($query->key, $query->operator, $query->value);
    }

    public function getKeywordRule($keyword)
    {
        return $this->getOption("keywords.$keyword");
    }

    public function getColumns()
    {
        return $this->getOption('columns')->keys();
    }

    public function getSearchables()
    {
        return $this->getOption('columns')->filter->searchable->keys();
    }

    public function isDateColumn($key)
    {
        return $this->getColumnRule($key)->date ?? false;
    }

    public function isBooleanColumn($key)
    {
        return $this->getColumnRule($key)->boolean ?? false;
    }
}