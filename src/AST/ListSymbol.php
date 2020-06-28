<?php

namespace Lorisleiva\LaravelSearchString\AST;

use Lorisleiva\LaravelSearchString\Visitors\Visitor;

class ListSymbol extends Symbol
{
    use CanHaveRule;
    use CanBeNegated;

    /** @var array */
    public $list;

    public function __construct(array $list)
    {
        $this->list = $list;
    }

    public function accept(Visitor $visitor)
    {
        return $visitor->visitList($this);
    }
}
