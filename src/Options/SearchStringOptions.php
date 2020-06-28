<?php

namespace Lorisleiva\LaravelSearchString\Options;

use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Lorisleiva\LaravelSearchString\AST\QuerySymbol;

trait SearchStringOptions
{
    /** @var Collection */
    protected $options;

    /** @var array */
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

    protected function resolveLonelyColumn($rule, $column): array
    {
        return is_string($column) ? [$column => $rule] : [$rule => null];
    }

    protected function castAsDate($model, $column): bool
    {
        return $model->hasCast($column, ['date', 'datetime'])
            || in_array($column, $model->getDates());
    }

    protected function castAsBoolean($model, $column): bool
    {
        return $model->hasCast($column, 'boolean');
    }

    /*
     * Helpers
     */

    public function getOptions(?string $type = null): Collection
    {
        if (! $type) {
            return $this->options;
        }

        return $this->options->get($type);
    }

    public function getRule($key, $operator = null, $value = null, $type = 'columns'): ?Rule
    {
        return $this->getOptions($type)->first(function ($rule) use ($key, $operator, $value) {
            return $rule->match($key, $operator, $value);
        });
    }

    public function getRuleForQuery(QuerySymbol $query, $type = 'columns'): ?Rule
    {
        return $this->getRule($query->key, $query->operator, $query->value, $type);
    }

    public function getColumns(): Collection
    {
        return $this->getOptions('columns')->keys();
    }

    public function getSearchables():Collection
    {
        return $this->getOptions('columns')->filter->searchable->keys();
    }
}
