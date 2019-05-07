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
        $this->buildQuery($query);

        return $query;
    }

    public function visitSolo(SoloSymbol $solo)
    {
        $this->buildSolo($solo);

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

    protected function buildQuery(QuerySymbol $query)
    {
        $rule = $this->manager->getRuleForQuery($query);

        if ($rule && $rule->date) {
            return $this->buildDate($query);
        }

        if (in_array($query->operator, ['in', 'not in'])) {
            return $this->buildInQuery($query);
        }

        if (in_array($query->operator, ['=', '!=']) && is_array($query->value)) {
            return $this->buildInQuery($query);
        }

        return $this->buildBasicQuery($query);
    }

    protected function buildSolo(SoloSymbol $solo)
    {
        $rule = $this->manager->getRule($solo->content);

        if ($rule && $rule->boolean && $rule->date) {
            return $this->buildDateAsBoolean($solo);
        }

        if ($rule && $rule->boolean) {
            return $this->buildBoolean($solo);
        }

        return $this->buildSearch($solo);
    }

    protected function buildBoolean(SoloSymbol $solo)
    {
        return $this->builder->where($solo->content, '=', ! $solo->negated, $this->boolean);
    }

    protected function buildDateAsBoolean(SoloSymbol $solo)
    {
        return $this->builder->whereNull($solo->content, $this->boolean, ! $solo->negated);
    }

    protected function buildSearch(SoloSymbol $solo)
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
            return $this->builder->where($where[0], $where[1], $where[2], $this->boolean);
        }

        return $this->builder->where($wheres->toArray(), null, null, $this->boolean);
    }

    protected function buildDate(QuerySymbol $query)
    {
        $dateWithPrecision = new DateWithPrecision($query->value);

        if (! $dateWithPrecision->carbon) {
            return $this->buildBasicQuery($query);
        }

        if (in_array($dateWithPrecision->precision, ['micro', 'second'])) {
            $query = new QuerySymbol($query->key, $query->operator, $dateWithPrecision->carbon);
            return $this->buildBasicQuery($query);
        }

        list($start, $end) = $dateWithPrecision->getRange();

        if (in_array($query->operator, ['>', '<', '>=', '<='])) {
            $extremity = in_array($query->operator, ['<', '>=']) ? $start : $end;
            $query = new QuerySymbol($query->key, $query->operator, $extremity);
            return $this->buildBasicQuery($query);
        }

        return $this->buildDateRange($query, $start, $end);
    }

    protected function buildDateRange(QuerySymbol $query, $start, $end)
    {
        $exclude = in_array($query->operator, ['!=', 'not in']);

        return $this->builder->where([
            [$query->key, ($exclude ? '<' : '>='), $start, $this->boolean],
            [$query->key, ($exclude ? '>' : '<='), $end, $this->boolean],
        ], null, null, $this->boolean);
    }

    protected function buildInQuery(QuerySymbol $query)
    {
        $notIn = in_array($query->operator, ['not in', '!=']);
        $value = array_wrap($query->value);
        return $this->builder->whereIn($query->key, $value, $this->boolean, $notIn);
    }

    protected function buildBasicQuery(QuerySymbol $query)
    {
        $value = $this->parseValue($query->value);
        return $this->builder->where($query->key, $query->operator, $value, $this->boolean);
    }

    protected function parseValue($value)
    {
        if (is_array($value)) {
            return $this->parseValue(array_get($value, 0, ''));
        }

        if (is_numeric($value)) {
            return $value + 0;
        }

        if ($value === 'NULL') {
            return null;
        }

        return $value;
    }
}