<?php

namespace Lorisleiva\LaravelSearchString\Visitors;

use Lorisleiva\LaravelSearchString\AST\NullSymbol;
use Lorisleiva\LaravelSearchString\AST\QuerySymbol;

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
