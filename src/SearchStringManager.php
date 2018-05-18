<?php

namespace Lorisleiva\LaravelSearchString;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Lorisleiva\LaravelSearchString\Exceptions\InvalidSearchStringException;
use Lorisleiva\LaravelSearchString\Lexer\Lexer;
use Lorisleiva\LaravelSearchString\Options\SearchStringOptions;
use Lorisleiva\LaravelSearchString\Parser\Parser;
use Lorisleiva\LaravelSearchString\Parser\QuerySymbol;
use Lorisleiva\LaravelSearchString\Parser\SoloSymbol;
use Lorisleiva\LaravelSearchString\Support\DateWithPrecision;
use Lorisleiva\LaravelSearchString\Visitor\BuildWhereClausesVisitor;
use Lorisleiva\LaravelSearchString\Visitor\ExtractKeywordVisitor;
use Lorisleiva\LaravelSearchString\Visitor\OptimizeAstVisitor;
use Lorisleiva\LaravelSearchString\Visitor\RemoveNotSymbolVisitor;

class SearchStringManager
{
    use SearchStringOptions;

    protected $model;

    public function __construct(Model $model)
    {
        $this->model = $model;
        $this->generateOptions($model);
    }

    public function lex($input, $tokenMap = null, $delimiter = null)
    {
        return (new Lexer($tokenMap, $delimiter))->lex($input);
    }

    public function parse($input)
    {
        return (new Parser)->parse($this->lex($input));
    }

    public function updateBuilder(Builder $builder, $input)
    {
        $ast = $this->parse($input);

        foreach ($this->getVisitors($builder) as $visitor) {
            $ast = $ast->accept($visitor);
        }
    }

    public function createBuilder($input)
    {
        $builder = $this->model->newQuery();
        $this->updateBuilder($builder, $input);
        return $builder;
    }
    
    public function getVisitors($builder)
    {
        return [
            new RemoveNotSymbolVisitor,
            new ExtractKeywordVisitor($builder, $this, 'order_by'),
            new ExtractKeywordVisitor($builder, $this, 'select'),
            new ExtractKeywordVisitor($builder, $this, 'limit'),
            new ExtractKeywordVisitor($builder, $this, 'offset'),
            new OptimizeAstVisitor,
            new BuildWhereClausesVisitor($builder, $this),
        ];
    }

    /**
     * Query Resolvers
     */

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

        $columns = in_array($query->operator, ['!=', 'not in'])
            ? $this->getColumns()->diff($columns)
            : $this->getColumns()->intersect($columns);

        $builder->select($columns->values()->toArray());
    }

    public function resolveLimitKeyword(Builder $builder, QuerySymbol $query, $lastQuery)
    {
        if (! ctype_digit($query->value)) {
            throw new InvalidSearchStringException('The limit must be an integer');
        }

        $builder->limit($query->value);
    }

    public function resolveOffsetKeyword(Builder $builder, QuerySymbol $query, $lastQuery)
    {
        if (! ctype_digit($query->value)) {
            throw new InvalidSearchStringException('The offset must be an integer');
        }

        $builder->offset($query->value);
    }

    public function resolveQuery(Builder $builder, QuerySymbol $query, $boolean)
    {
        $rule = $this->getColumnRuleForQuery($query);

        if ($rule && $rule->date) {
            return $this->resolveDate($builder, $query, $boolean);
        }

        if (in_array($query->operator, ['in', 'not in'])) {
            return $this->resolveInQuery($builder, $query, $boolean);
        }

        return $this->resolveBasicQuery($builder, $query, $boolean);
    }

    public function resolveSolo(Builder $builder, SoloSymbol $solo, $boolean)
    {
        $rule = $this->getColumnRule($solo->content);

        if ($rule && $rule->boolean && $rule->date) {
            return $this->resolveDateAsBoolean($builder, $solo, $boolean);
        }

        if ($rule && $rule->boolean) {
            return $this->resolveBoolean($builder, $solo, $boolean);
        }

        return $this->resolveSearch($builder, $solo, $boolean);
    }

    protected function resolveInQuery(Builder $builder, QuerySymbol $query, $boolean)
    {
        $notIn = $query->operator === 'not in';
        return $builder->whereIn($query->key, $query->value, $boolean, $notIn);
    }

    protected function resolveBasicQuery(Builder $builder, QuerySymbol $query, $boolean)
    {
        return $builder->where($query->key, $query->operator, $query->value, $boolean);
    }

    protected function resolveBoolean(Builder $builder, SoloSymbol $solo, $boolean)
    {
        return $builder->where($solo->content, '=', ! $solo->negated, $boolean);
    }

    protected function resolveDateAsBoolean(Builder $builder, SoloSymbol $solo, $boolean)
    {
        return $builder->whereNull($solo->content, $boolean, ! $solo->negated);
    }

    protected function resolveSearch(Builder $builder, SoloSymbol $solo, $boolean)
    {
        $wheres = $this->getSearchables()->map(function ($column) use ($solo) {
            $boolean = $solo->negated ? 'and' : 'or';
            $operator = $solo->negated ? 'not like' : 'like';
            return [$column, $operator, "%$solo->content%", $boolean];
        });

        if ($wheres->isEmpty()) {
            return;
        }

        if ($wheres->count() === 1) {
            $where = $wheres->first();
            return $builder->where($where[0], $where[1], $where[2], $boolean);
        }

        return $builder->where($wheres->toArray(), null, null, $boolean);
    }

    protected function resolveDate(Builder $builder, QuerySymbol $query, $boolean)
    {
        $dateWithPrecision = new DateWithPrecision($query->value);

        if (! $dateWithPrecision->carbon) {
            return $this->resolveBasicQuery($builder, $query, $boolean);
        }

        $exactPrecision = in_array($dateWithPrecision->precision, ['micro', 'second']);
        $comparaison = in_array($query->operator, ['>', '<', '>=', '<=']);

        if ($exactPrecision || $comparaison) {
            $query = new QuerySymbol($query->key, $query->operator, $dateWithPrecision->carbon);
            return $this->resolveBasicQuery($builder, $query, $boolean);
        }

        list($start, $end) = $dateWithPrecision->getRange();
        return $this->resolveDateRange($builder, $query, $start, $end, $boolean);
    }

    protected function resolveDateRange(Builder $builder, QuerySymbol $query, $start, $end, $boolean)
    {
        $exclude = in_array($query->operator, ['!=', 'not in']);

        return $builder->where([
            [$query->key, ($exclude ? '<' : '>='), $start, $boolean],
            [$query->key, ($exclude ? '>' : '<='), $end, $boolean],
        ], null, null, $boolean);
    }
}