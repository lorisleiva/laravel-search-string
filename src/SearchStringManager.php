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
use Lorisleiva\LaravelSearchString\Parser\SearchSymbol;
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
        return (new Parser($this))->parse($this->lex($input), $this);
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

    /**
     * Overiddables
     */
    
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

    public function resolveQueryWhereClause(Builder $builder, QuerySymbol $query, $boolean)
    {
        switch ($query->operator) {
            case 'in':
                return $builder->whereIn($query->key, $query->value, $boolean);

            case 'not in':
                return $builder->whereNotIn($query->key, $query->value, $boolean);
            
            default:
                if (is_null($query->value)) {
                    return $builder->whereNull($query->key, $boolean, $query->operator === '!=');
                }

                return $builder->where($query->key, $query->operator, $query->value, $boolean);
        }
    }

    public function resolveSearchWhereClause(Builder $builder, SearchSymbol $search, $boolean)
    {
        $searchables = $this->getSearchables();

        $wheres = $searchables->map(function ($column) use ($search) {
            $boolean = $search->exclude ? 'and' : 'or';
            $operator = $search->exclude ? 'not like' : 'like';
            return [$column, $operator, "%$search->content%", $boolean];
        });

        if ($wheres->isEmpty()) {
            return;
        }

        if ($wheres->count() === 1) {
            $where = $wheres->first();
            return $builder->where($where[0], $where[1], $where[2], $boolean);
        }

        return $boolean === 'or'
            ? $builder->orWhere($wheres->toArray())
            : $builder->where($wheres->toArray());
    }
}