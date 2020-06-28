<?php

namespace Lorisleiva\LaravelSearchString\Visitors;

use Lorisleiva\LaravelSearchString\AST\ListSymbol;
use Lorisleiva\LaravelSearchString\Options\ColumnRule;
use Lorisleiva\LaravelSearchString\AST\OrSymbol;
use Lorisleiva\LaravelSearchString\AST\AndSymbol;
use Lorisleiva\LaravelSearchString\AST\SoloSymbol;
use Lorisleiva\LaravelSearchString\AST\QuerySymbol;
use Lorisleiva\LaravelSearchString\SearchStringManager;
use Lorisleiva\LaravelSearchString\Support\DateWithPrecision;

class BuildColumnsVisitor extends Visitor
{
    /** @var SearchStringManager */
    protected $manager;
    protected $builder;
    protected $boolean;

    public function __construct(SearchStringManager $manager, $builder, $boolean = 'and')
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

    public function visitSolo(SoloSymbol $solo)
    {
        $this->buildSolo($solo);

        return $solo;
    }

    public function visitQuery(QuerySymbol $query)
    {
        $this->buildQuery($query);

        return $query;
    }

    public function visitList(ListSymbol $list)
    {
        $this->buildList($list);

        return $list;
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

    protected function buildSolo(SoloSymbol $solo)
    {
        if ($solo->rule && $solo->rule->boolean && $solo->rule->date) {
            return $this->builder->whereNull($solo->rule->column, $this->boolean, ! $solo->negated);
        }

        if ($solo->rule && $solo->rule->boolean) {
            return $this->builder->where($solo->rule->column, '=', ! $solo->negated, $this->boolean);
        }

        return $this->buildSearch($solo);
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

    protected function buildQuery(QuerySymbol $query)
    {
        /** @var ColumnRule $rule */
        if (! $rule = $query->rule) {
            return;
        }

        $query->value = $this->mapValue($query->value, $rule);

        if ($rule->date) {
            return $this->buildDate($query, $rule);
        }

        return $this->buildBasicQuery($query, $rule);
    }

    protected function buildList(ListSymbol $list)
    {
        /** @var ColumnRule $rule */
        if (! $rule = $list->rule) {
            return;
        }

        $list->values = $this->mapValue($list->values, $rule);

        return $this->builder->whereIn($rule->column, $list->values, $this->boolean, $list->negated);
    }

    protected function buildDate(QuerySymbol $query, ColumnRule $rule)
    {
        $dateWithPrecision = new DateWithPrecision($query->value);

        if (! $dateWithPrecision->carbon) {
            return $this->buildBasicQuery($query, $rule);
        }

        if (in_array($dateWithPrecision->precision, ['micro', 'second'])) {
            $query->value = $dateWithPrecision->carbon;
            return $this->buildBasicQuery($query, $rule);
        }

        list($start, $end) = $dateWithPrecision->getRange();

        if (in_array($query->operator, ['>', '<', '>=', '<='])) {
            $query->value = in_array($query->operator, ['<', '>=']) ? $start : $end;
            return $this->buildBasicQuery($query, $rule);
        }

        return $this->buildDateRange($query, $start, $end, $rule);
    }

    protected function buildDateRange(QuerySymbol $query, $start, $end, ColumnRule $rule)
    {
        $exclude = in_array($query->operator, ['!=', 'not in']);

        return $this->builder->where([
            [$rule->column, ($exclude ? '<' : '>='), $start, $this->boolean],
            [$rule->column, ($exclude ? '>' : '<='), $end, $this->boolean],
        ], null, null, $this->boolean);
    }

    protected function buildBasicQuery(QuerySymbol $query, ColumnRule $rule)
    {
        return $this->builder->where($rule->column, $query->operator, $query->value, $this->boolean);
    }

    protected function mapValue($value, ColumnRule $rule)
    {
        if (is_array($value)) {
            return array_map(function ($value) use ($rule) {
                return $rule->map->has($value) ? $rule->map->get($value) : $value;
            }, $value);
        }

        if ($rule->map->has($value)) {
            return $rule->map->get($value);
        }

        if (is_numeric($value)) {
            return $value + 0;
        }

        return $value;
    }
}
