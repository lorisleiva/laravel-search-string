<?php

namespace Lorisleiva\LaravelSearchString\Visitors;

use Lorisleiva\LaravelSearchString\AST\EmptySymbol;
use Lorisleiva\LaravelSearchString\AST\QuerySymbol;
use Lorisleiva\LaravelSearchString\AST\SoloSymbol;
use Lorisleiva\LaravelSearchString\SearchStringManager;

class AttachRulesVisitor extends Visitor
{
    /** @var SearchStringManager */
    protected $manager;

    public function __construct(SearchStringManager $manager)
    {
        $this->manager = $manager;
    }

    public function visitSolo(SoloSymbol $solo)
    {
        if ($rule = $this->manager->getRuleForQuery($solo, 'column')) {
            $solo->attachRule($rule);
        }

        return $solo;
    }

    public function visitQuery(QuerySymbol $query)
    {
        if ($rule = $this->manager->getRuleForQuery($query, 'keywords')) {
            $query->attachRule($rule);
        }

        elseif ($rule = $this->manager->getRuleForQuery($query, 'column')) {
            $query->attachRule($rule);
        }

        return $query;
    }
}
