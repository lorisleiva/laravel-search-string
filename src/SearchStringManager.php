<?php

namespace Lorisleiva\LaravelSearchString;

use Lorisleiva\LaravelSearchString\Lexer\Lexer;
use Lorisleiva\LaravelSearchString\Parser\Parser;

class SearchStringManager
{
    protected $tokenMap = null;

    public function withTokenMap($tokenMap)
    {
        $this->tokenMap = $tokenMap;
    }

    public function lex($input)
    {
        $tokenMap = $this->tokenMap ?? config('search-string.token_map');
        return (new Lexer($tokenMap))->lex($input);
    }

    public function parse($input)
    {
        return (new Parser)->parse($this->lex($input));
    }

    public function queryBuilderFor($model, $input)
    {
        // TODO
        return $this->parse($input);
    }
}