<?php

namespace Lorisleiva\LaravelSearchString\Visitors;

use Illuminate\Support\Arr;
use Lorisleiva\LaravelSearchString\Options\ColumnRule;
use Lorisleiva\LaravelSearchString\Options\Rule;
use Lorisleiva\LaravelSearchString\AST\OrSymbol;
use Lorisleiva\LaravelSearchString\AST\AndSymbol;
use Lorisleiva\LaravelSearchString\AST\NotSymbol;
use Lorisleiva\LaravelSearchString\AST\SoloSymbol;
use Lorisleiva\LaravelSearchString\AST\QuerySymbol;
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
        $rule = $this->manager->getRule($solo->content);

        if ($rule && $rule->boolean && $rule->date) {
            return $this->builder->whereNull($rule->column, $this->boolean, ! $solo->negated);
        }

        if ($rule && $rule->boolean) {
            return $this->builder->where($rule->column, '=', ! $solo->negated, $this->boolean);
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
        if (! $rule = $this->manager->getRuleForQuery($query)) {
            return;
        }

        // Update the query value if the rule defines a mapping.
        $query = $this->mapQueryValue($query, $rule);

        if ($rule->date) {
            return $this->buildDate($query, $rule);
        }

        if (in_array($query->operator, ['in', 'not in'])) {
            return $this->buildInQuery($query, $rule);
        }

        if (in_array($query->operator, ['=', '!=']) && is_array($query->value)) {
            return $this->buildInQuery($query, $rule);
        }

        return $this->buildBasicQuery($query, $rule);
    }

    protected function buildDate(QuerySymbol $query, Rule $rule)
    {
        $dateWithPrecision = new DateWithPrecision($query->value);

        if (! $dateWithPrecision->carbon) {
            return $this->buildBasicQuery($query, $rule);
        }

        if (in_array($dateWithPrecision->precision, ['micro', 'second'])) {
            $query = new QuerySymbol($query->key, $query->operator, $dateWithPrecision->carbon);
            return $this->buildBasicQuery($query, $rule);
        }

        list($start, $end) = $dateWithPrecision->getRange();

        if (in_array($query->operator, ['>', '<', '>=', '<='])) {
            $extremity = in_array($query->operator, ['<', '>=']) ? $start : $end;
            $query = new QuerySymbol($query->key, $query->operator, $extremity);
            return $this->buildBasicQuery($query, $rule);
        }

        return $this->buildDateRange($query, $start, $end, $rule);
    }

    protected function buildDateRange(QuerySymbol $query, $start, $end, Rule $rule)
    {
        $exclude = in_array($query->operator, ['!=', 'not in']);

        return $this->builder->where([
            [$rule->column, ($exclude ? '<' : '>='), $start, $this->boolean],
            [$rule->column, ($exclude ? '>' : '<='), $end, $this->boolean],
        ], null, null, $this->boolean);
    }

    protected function buildInQuery(QuerySymbol $query, Rule $rule)
    {
        $notIn = in_array($query->operator, ['not in', '!=']);
        $value = Arr::wrap($query->value);
        return $this->builder->whereIn($rule->column, $value, $this->boolean, $notIn);
    }

    protected function buildBasicQuery(QuerySymbol $query, Rule $rule)
    {
        $value = $this->parseValue($query->value);
        return $this->builder->where($rule->column, $query->operator, $value, $this->boolean);
    }

    protected function parseValue($value)
    {
        if (is_array($value)) {
            return $this->parseValue(Arr::get($value, 0, ''));
        }

        if (is_numeric($value)) {
            return $value + 0;
        }

        if ($value === 'NULL') {
            return null;
        }

        return $value;
    }

    protected function mapQueryValue(QuerySymbol $query, ColumnRule $rule)
    {
        if ($rule->map && $rule->map->has($query->value)) {
            return new QuerySymbol($query->key, $query->operator, $rule->map->get($query->value));
        }

        return $query;
    }
}
