<?php

namespace Lorisleiva\LaravelSearchString\Lexer;

class Token
{
    const ALL = [
        'T_COMPARATOR',
        'T_ASSIGN',
        'T_AND',
        'T_OR',
        'T_NOT',
        'T_IN',
        'T_LIST_SEPARATOR',
        'T_LPARENT',
        'T_RPARENT',
        'T_SPACE',
        'T_STRING',
        'T_TERM',
        'T_EOL',
    ];

    public $type;
    public $content;

    function __construct($type, $content)
    {
        $this->type = $type;
        $this->content = $content;
    }

    public function hasType(...$types)
    {
        return in_array($this->type, $types);
    }

    public function __toString()
    {
        return "$this->type<$this->content>";
    }
}