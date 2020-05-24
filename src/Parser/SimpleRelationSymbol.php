<?php

namespace Lorisleiva\LaravelSearchString\Parser;

use Lorisleiva\LaravelSearchString\Visitor\Visitor;

class SimpleRelationSymbol extends RelationSymbol
{
    public function accept(Visitor $visitor)
    {
        return $visitor->visitSimpleRelation($this);
    }
}