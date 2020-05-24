<?php

namespace Lorisleiva\LaravelSearchString\Parser;

use Lorisleiva\LaravelSearchString\Visitor\Visitor;

abstract class Symbol
{
    abstract public function accept(Visitor $visitor);

    public static function termHasDot($term)
    {
        return strpos($term, '.') !== false;
    }
}