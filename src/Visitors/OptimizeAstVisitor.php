<?php

namespace Lorisleiva\LaravelSearchString\Visitors;

use Illuminate\Support\Collection;
use Lorisleiva\LaravelSearchString\AST\AndSymbol;
use Lorisleiva\LaravelSearchString\AST\NotSymbol;
use Lorisleiva\LaravelSearchString\AST\EmptySymbol;
use Lorisleiva\LaravelSearchString\AST\OrSymbol;
use Lorisleiva\LaravelSearchString\AST\RelationshipSymbol;
use Lorisleiva\LaravelSearchString\AST\Symbol;

class OptimizeAstVisitor extends Visitor
{
    public function visitOr(OrSymbol $or)
    {
        $leaves = $or->expressions->map->accept($this)
            ->flatMap(function ($leaf) {
                return $leaf instanceof OrSymbol ? $leaf->expressions : [$leaf];
            })
            ->filter(function ($leaf) {
                return ! $leaf instanceof EmptySymbol;
            });

        $leaves = $this->mergeEquivalentRelationshipSymbols($leaves, OrSymbol::class);

        return $this->flattenNestedExpressions($leaves, OrSymbol::class);
    }

    public function visitAnd(AndSymbol $and)
    {
        $leaves = $and->expressions->map->accept($this)
            ->flatMap(function ($leaf) {
                return $leaf instanceof AndSymbol ? $leaf->expressions : [$leaf];
            })
            ->filter(function ($leaf) {
                return ! $leaf instanceof EmptySymbol;
            });

        $leaves = $this->mergeEquivalentRelationshipSymbols($leaves, AndSymbol::class);

        return $this->flattenNestedExpressions($leaves, AndSymbol::class);
    }

    public function visitNot(NotSymbol $not)
    {
        $leaf = $not->expression->accept($this);
        return $leaf instanceof EmptySymbol ? new EmptySymbol : new NotSymbol($leaf);
    }

    public function mergeEquivalentRelationshipSymbols(Collection $leaves, string $symbolClass): Collection
    {
        return $leaves
            ->reduce(function (Collection $acc, Symbol $symbol) {
                if ($group = $this->findRelationshipGroup($acc, $symbol)) {
                    $group->push($symbol);
                } else {
                    $acc->push(collect([$symbol]));
                }

                return $acc;
            }, collect())
            ->map(function (Collection $group) use ($symbolClass) {
                return  $group->count() > 1
                    ? $this->mergeRelationshipGroup($group, $symbolClass)
                    : $group->first();
            });
    }

    public function findRelationshipGroup(Collection $leafGroups, Symbol $symbol): ?Collection
    {
        if (! $symbol instanceof RelationshipSymbol) {
            return null;
        }

        return $leafGroups->first(function (Collection $group) use ($symbol) {
            return $symbol->match($group->first());
        });
    }

    public function mergeRelationshipGroup(Collection $relationshipGroup, string $symbolClass): RelationshipSymbol
    {
        $relationshipSymbol = $relationshipGroup->first();
        $expressions = $relationshipGroup->map->expression;
        $relationshipSymbol->expression = $this->flattenNestedExpressions($expressions, $symbolClass);

        return $relationshipSymbol;
    }

    public function flattenNestedExpressions(Collection $expressions, string $symbolClass): Symbol
    {
        $expressions = $expressions->filter(function ($leaf) {
            return ! $leaf instanceof EmptySymbol;
        });

        if ($expressions->isEmpty()) {
            return new EmptySymbol();
        }

        if ($expressions->count() === 1) {
            return $expressions->first();
        }

        return new $symbolClass($expressions);
    }
}
