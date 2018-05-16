<?php

namespace Lorisleiva\LaravelSearchString\Visitor;

use Lorisleiva\LaravelSearchString\Parser\AndSymbol;
use Lorisleiva\LaravelSearchString\Parser\NotSymbol;
use Lorisleiva\LaravelSearchString\Parser\NullSymbol;
use Lorisleiva\LaravelSearchString\Parser\OrSymbol;
use Lorisleiva\LaravelSearchString\Parser\QuerySymbol;
use Lorisleiva\LaravelSearchString\Parser\SearchSymbol;

class BuildWhereClausesVisitor implements Visitor
{
    protected $builder;
    protected $manager;
    protected $boolean;

    public function __construct($builder, $manager, $boolean = 'and')
    {
        $this->builder = $builder;
        $this->manager = $manager;
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
        $this->manager->resolveQueryWhereClause($this->builder, $query, $this->boolean);

        return $query;
    }

    public function visitSearch(SearchSymbol $search)
    {
        $this->manager->resolveSearchWhereClause($this->builder, $search, $this->boolean);

        return $search;
    }

    public function visitNull(NullSymbol $null)
    {
        return $null;
    }

    public function createNestedBuilderWith($expressions, $newBoolean)
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
}