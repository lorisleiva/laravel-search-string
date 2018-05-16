<?php

namespace Lorisleiva\LaravelSearchString;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Lorisleiva\LaravelSearchString\Exceptions\InvalidSearchStringException;
use Lorisleiva\LaravelSearchString\Lexer\Lexer;
use Lorisleiva\LaravelSearchString\Parser\Parser;
use Lorisleiva\LaravelSearchString\Parser\QuerySymbol;
use Lorisleiva\LaravelSearchString\Parser\SearchSymbol;
use Lorisleiva\LaravelSearchString\Visitor\ExtractKeywordVisitor;
use Lorisleiva\LaravelSearchString\Visitor\OptimizeAstVisitor;
use Lorisleiva\LaravelSearchString\Visitor\RemoveNotSymbolVisitor;

class SearchStringManager
{
    protected $model;

    protected $fallbackOptions = [
        'columns' => [
            'visible' => null,
            'searchable' => null,
            'boolean' => null,
            'date' => null,
        ],
        'keywords' => [
            'order_by' => 'sort',
            'select' => 'fields',
            'limit' => 'limit',
            'offset' => 'from',
        ],
    ];

    public function __construct(Model $model)
    {
        $this->model = $model;
    }

    public function lex($input, $tokenMap = null, $delimiter = null)
    {
        return (new Lexer($tokenMap, $delimiter))->lex($input);
    }

    public function parse($input)
    {
        return (new Parser($this))->parse($this->lex($input), $this);
    }

    public function updateBuilder(Builder $builder, $input)
    {
        $this->parse($input)
             ->accept(new RemoveNotSymbolVisitor)
             ->accept(new ExtractKeywordVisitor($builder, $this, 'order_by'))
             ->accept(new ExtractKeywordVisitor($builder, $this, 'select'))
             ->accept(new ExtractKeywordVisitor($builder, $this, 'limit'))
             ->accept(new ExtractKeywordVisitor($builder, $this, 'offset'))
             ->accept(new OptimizeAstVisitor);
    }

    public function createBuilder($input)
    {
        $builder = $model->newQuery();
        $this->updateBuilder($builder, $input);
        return $builder;
    }

    public function getOptions()
    {
        return array_replace_recursive(
            $this->fallbackOptions,
            array_get(config('search-string'), 'default', []),
            array_get(config('search-string'), get_class($this->model), []),
            $this->model->searchStringOptions ?? []
        );
    }

    public function getOption($key, $default = null)
    {
        return array_get($this->getOptions(), $key, $default);
    }

    public function getKeywordRule($keyword)
    {
        return $this->parseKeywordRule($this->getOption("keywords.$keyword"), $keyword);
    }

    public function parseKeywordRule($rule, $keyword)
    {
        $key = is_string($rule) ? $rule : array_get($rule, 'key', $keyword);

        $rule = array_wrap($rule);
        $operator = array_get($rule, 'operator', '/.*/');
        $value = array_get($rule, 'value', '/.*/');

        return (object) collect(compact('key', 'operator', 'value'))->map(function ($pattern) {
            if (@preg_match($pattern, null) === false) {
                $pattern = '/^' . preg_quote($pattern, '/') . '$/';
            }

            return $pattern;
        })->toArray();
    }

    /**
     * Overiddables
     */
    
    public function matchKeyword($query, $rule)
    {
        $keyMatch = preg_match($rule->key, $query->key);
        $operatorMatch = preg_match($rule->operator, $query->operator);
        $valueMatch = collect($query->value)->every(function ($value) use ($rule) {
            return preg_match($rule->value, $value);
        });

        return $keyMatch && $operatorMatch && $valueMatch;
    }

    public function resolveOrderByKeyword(Builder $builder, QuerySymbol $query, $lastQuery)
    {
        $builder->getQuery()->orders = null;

        collect($query->value)->each(function ($value) use ($builder) {
            $desc = starts_with($value, '-') ? 'desc' : 'asc';
            $column = starts_with($value, '-') ? str_after($value, '-') : $value;
            $builder->orderBy($column, $desc);
        });
    }

    public function resolveSelectKeyword(Builder $builder, QuerySymbol $query, $lastQuery)
    {
        $columns = array_wrap($query->value);

        if (! in_array($query->operator, ['!=', 'not in'])) {
            return $builder->select($columns);
        }

        $visibleColumns = $this->getOption('columns.visible');
        $builder->select(array_values(array_diff($visibleColumns, $columns)));
    }

    public function resolveLimitKeyword(Builder $builder, QuerySymbol $query, $lastQuery)
    {
        if (! ctype_digit($query->value)) {
            throw new InvalidSearchStringException;
        }

        $builder->limit($query->value);
    }

    public function resolveOffsetKeyword(Builder $builder, QuerySymbol $query, $lastQuery)
    {
        if (! ctype_digit($query->value)) {
            throw new InvalidSearchStringException;
        }

        $builder->offset($query->value);
    }

    public function resolveWhereClause(Builder $builder, QuerySymbol $query)
    {
        //
    }

    public function resolveSearch(Builder $builder, SearchSymbol $search)
    {
        //
    }
}