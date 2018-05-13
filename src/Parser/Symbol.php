<?php

namespace Lorisleiva\LaravelSearchString\Parser;

use Lorisleiva\LaravelSearchString\Visitor\Visitor;

abstract class Symbol
{
    abstract public function accept(Visitor $visitor);
}