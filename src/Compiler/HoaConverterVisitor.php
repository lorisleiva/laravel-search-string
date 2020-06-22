<?php

namespace Lorisleiva\LaravelSearchString\Compiler;

use Hoa\Visitor\Element;
use Hoa\Visitor\Visit;

class HoaConverterVisitor implements Visit
{
    public function visit(Element $element, &$handle = null, $eldnah = null)
    {
        dump($element);
    }
}
