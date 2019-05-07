<?php

namespace Lorisleiva\LaravelSearchString\Visitor;

use Lorisleiva\LaravelSearchString\Parser\NullSymbol;
use Lorisleiva\LaravelSearchString\Parser\QuerySymbol;

class RemoveKeywordsVisitor extends Visitor
{
    protected $manager;

    public function __construct($manager)
    {
        $this->manager = $manager;
    }

    public function visitQuery(QuerySymbol $query)
    {
        return $this->manager->getRuleForQuery($query, 'keywords')
            ? new NullSymbol
            : $query;
    }
}