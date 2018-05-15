<?php

namespace Lorisleiva\LaravelSearchString\Visitor;

use Lorisleiva\LaravelSearchString\Parser\AndSymbol;
use Lorisleiva\LaravelSearchString\Parser\NotSymbol;
use Lorisleiva\LaravelSearchString\Parser\NullSymbol;
use Lorisleiva\LaravelSearchString\Parser\OrSymbol;
use Lorisleiva\LaravelSearchString\Parser\QuerySymbol;

interface Visitor
{
    public function visitOr(OrSymbol $or);
    public function visitAnd(AndSymbol $and);
    public function visitNot(NotSymbol $not);
    public function visitQuery(QuerySymbol $query);
    public function visitNull(NullSymbol $null);
}