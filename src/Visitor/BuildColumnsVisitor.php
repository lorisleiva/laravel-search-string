<?php

namespace Lorisleiva\LaravelSearchString\Visitor;

use Illuminate\Database\Eloquent\Builder;
use Lorisleiva\LaravelSearchString\Parser\AndSymbol;
use Lorisleiva\LaravelSearchString\Parser\NotSymbol;
use Lorisleiva\LaravelSearchString\Parser\OrSymbol;
use Lorisleiva\LaravelSearchString\Parser\QuerySymbol;
use Lorisleiva\LaravelSearchString\Parser\SoloSymbol;
use Lorisleiva\LaravelSearchString\Support\DateWithPrecision;

class BuildColumnsVisitor extends Visitor
{
    protected $manager;
    protected $builder;
    protected $boolean;

    public function __construct($manager, $builder, $boolean = 'and')
    {
        $this->manager = $manager;
        $this->builder = $builder;
        $this->boolean = $boolean;
    }

    public function visitOr(OrSymbol $or)
    {
        $this->createNestedBuilderWith($or->expressions, 'or');

        return $or;
    }

    public function visitAnd(AndSymbol $and)
    {
        $this->createNestedBuilderWith($and->expressions, 'and');

        return $and;
    }

    public function visitNot(NotSymbol $not)
    {
        $not->expression->accept($this);

        return $not;
    }

    public function visitQuery(QuerySymbol $query)
    {
        $this->buildQuery($this->builder, $query, $this->boolean);

        return $query;
    }

    public function visitSolo(SoloSymbol $solo)
    {
        $this->buildSolo($this->builder, $solo, $this->boolean);

        return $solo;
    }

    protected function createNestedBuilderWith($expressions, $newBoolean)
    {
        // Save and update the new boolean.
        $originalBoolean = $this->boolean;
        $this->boolean = $newBoolean;

        // Create nested builder that follows the original boolean.
        $this->builder->where(function ($nestedBuilder) use ($expressions) {

            // Save and update the new builder.
            $originalBuilder = $this->builder;
            $this->builder = $nestedBuilder;

            // Recursively generate the nested builder.
            $expressions->each->accept($this);

            // Restore the original builder.
            $this->builder = $originalBuilder;

        }, null, null, $originalBoolean);

        // Restore the original boolean.
        $this->boolean = $originalBoolean;
    }

    protected function buildQuery(Builder $builder, QuerySymbol $query, $boolean)
    {
        $rule = $this->manager->getRuleForQuery($query);

        if ($rule && $rule->date) {
            return $this->buildDate($builder, $query, $boolean);
        }

        if (in_array($query->operator, ['in', 'not in'])) {
            return $this->buildInQuery($builder, $query, $boolean);
        }

        return $this->buildBasicQuery($builder, $query, $boolean);
    }

    protected function buildSolo(Builder $builder, SoloSymbol $solo, $boolean)
    {
        $rule = $this->manager->getRule($solo->content);

        if ($rule && $rule->boolean && $rule->date) {
            return $this->buildDateAsBoolean($builder, $solo, $boolean);
        }

        if ($rule && $rule->boolean) {
            return $this->buildBoolean($builder, $solo, $boolean);
        }

        return $this->buildSearch($builder, $solo, $boolean);
    }

    protected function buildBoolean(Builder $builder, SoloSymbol $solo, $boolean)
    {
        return $builder->where($solo->content, '=', ! $solo->negated, $boolean);
    }

    protected function buildDateAsBoolean(Builder $builder, SoloSymbol $solo, $boolean)
    {
        return $builder->whereNull($solo->content, $boolean, ! $solo->negated);
    }

    protected function buildSearch(Builder $builder, SoloSymbol $solo, $boolean)
    {
        $wheres = $this->manager->getSearchables()->map(function ($column) use ($solo) {
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

    protected function buildDate(Builder $builder, QuerySymbol $query, $boolean)
    {
        $dateWithPrecision = new DateWithPrecision($query->value);

        if (! $dateWithPrecision->carbon) {
            return $this->buildBasicQuery($builder, $query, $boolean);
        }

        if (in_array($dateWithPrecision->precision, ['micro', 'second'])) {
            $query = new QuerySymbol($query->key, $query->operator, $dateWithPrecision->carbon);
            return $this->buildBasicQuery($builder, $query, $boolean);
        }

        list($start, $end) = $dateWithPrecision->getRange();

        if (in_array($query->operator, ['>', '<', '>=', '<='])) {
            $extremity = in_array($query->operator, ['<', '>=']) ? $start : $end;
            $query = new QuerySymbol($query->key, $query->operator, $extremity);
            return $this->buildBasicQuery($builder, $query, $boolean);
        }

        return $this->buildDateRange($builder, $query, $start, $end, $boolean);
    }

    protected function buildDateRange(Builder $builder, QuerySymbol $query, $start, $end, $boolean)
    {
        $exclude = in_array($query->operator, ['!=', 'not in']);

        return $builder->where([
            [$query->key, ($exclude ? '<' : '>='), $start, $boolean],
            [$query->key, ($exclude ? '>' : '<='), $end, $boolean],
        ], null, null, $boolean);
    }

    protected function buildInQuery(Builder $builder, QuerySymbol $query, $boolean)
    {
        $notIn = $query->operator === 'not in';
        return $builder->whereIn($query->key, $query->value, $boolean, $notIn);
    }

    protected function buildBasicQuery(Builder $builder, QuerySymbol $query, $boolean)
    {
        return $builder->where($query->key, $query->operator, $query->value, $boolean);
    }
}