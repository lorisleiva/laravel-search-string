<?php

namespace Lorisleiva\LaravelSearchString\Lexer;

class Token
{
    public $type;
    public $content;

    function __construct($type, $content)
    {
        $this->type = $type;
        $this->content = $content;
    }

    public function __toString()
    {
        return "$this->type<$this->content>";
    }
}