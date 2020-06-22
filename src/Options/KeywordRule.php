<?php

namespace Lorisleiva\LaravelSearchString\Options;

use Lorisleiva\LaravelSearchString\Options\Rule;
use Lorisleiva\LaravelSearchString\AST\QuerySymbol;

class KeywordRule extends Rule
{
    public function __construct($column, $rule = null)
    {
        parent::__construct($column, $rule);
    }
}
