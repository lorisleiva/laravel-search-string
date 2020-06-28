<?php

namespace Lorisleiva\LaravelSearchString\Visitors;

use Lorisleiva\LaravelSearchString\AST\ListSymbol;
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
        if ($rule = $this->manager->getColumnRule($solo->content)) {
            return $solo->attachRule($rule);
        }

        return $solo;
    }

    public function visitQuery(QuerySymbol $query)
    {
        if ($rule = $this->manager->getRule($query->key)) {
            return $query->attachRule($rule);
        }

        return $query;
    }

    public function visitList(ListSymbol $list)
    {
        if ($rule = $this->manager->getRule($list->key)) {
            return $list->attachRule($rule);
        }

        return $list;
    }
}
