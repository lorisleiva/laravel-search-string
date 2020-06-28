<?php

namespace Lorisleiva\LaravelSearchString\AST;

use Lorisleiva\LaravelSearchString\Visitors\Visitor;

class ListSymbol extends Symbol
{
    use CanHaveRule;
    use CanBeNegated;

    /** @var string */
    public $key;

    /** @var array */
    public $list;

    public function __construct(string $key, array $list)
    {
        $this->key = $key;
        $this->list = $list;
    }

    public function accept(Visitor $visitor)
    {
        return $visitor->visitList($this);
    }
}
