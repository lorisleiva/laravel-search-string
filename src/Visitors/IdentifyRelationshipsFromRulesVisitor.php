<?php

namespace Lorisleiva\LaravelSearchString\Visitors;

use Lorisleiva\LaravelSearchString\AST\EmptySymbol;
use Lorisleiva\LaravelSearchString\AST\QuerySymbol;
use Lorisleiva\LaravelSearchString\AST\RelationshipSymbol;
use Lorisleiva\LaravelSearchString\AST\SoloSymbol;

class IdentifyRelationshipsFromRulesVisitor extends Visitor
{
    public function visitSolo(SoloSymbol $solo)
    {
        if (! $solo->rule || ! $solo->rule->relationship) {
            return $solo;
        }

        return new RelationshipSymbol($solo->content, new EmptySymbol());
    }

    public function visitQuery(QuerySymbol $query)
    {
        if (! $query->rule || ! $query->rule->relationship) {
            return $query;
        }

        return new RelationshipSymbol($query->key, new EmptySymbol(), $query->operator, $query->value);
    }
}
