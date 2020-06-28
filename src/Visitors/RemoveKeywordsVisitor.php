<?php

namespace Lorisleiva\LaravelSearchString\Visitors;

use Lorisleiva\LaravelSearchString\AST\EmptySymbol;
use Lorisleiva\LaravelSearchString\AST\ListSymbol;
use Lorisleiva\LaravelSearchString\AST\QuerySymbol;
use Lorisleiva\LaravelSearchString\Options\KeywordRule;

class RemoveKeywordsVisitor extends Visitor
{
    public function visitQuery(QuerySymbol $query)
    {
        return $query->rule instanceof KeywordRule ? new EmptySymbol : $query;
    }

    public function visitList(ListSymbol $list)
    {
        return $list->rule instanceof KeywordRule ? new EmptySymbol : $list;
    }
}
