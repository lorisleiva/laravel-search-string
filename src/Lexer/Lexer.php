<?php

namespace Lorisleiva\LaravelSearchString\Lexer;

use Lorisleiva\LaravelSearchString\Exceptions\InvalidSearchStringException;
use Lorisleiva\LaravelSearchString\Lexer\Token;

class Lexer
{
    protected $regex;
    protected $tokenTypes;

    public function __construct(array $tokenMap, $delimiter = '~') {
        $gluedRegexes = implode('|', array_keys($tokenMap));
        $this->regex =  $delimiter . $gluedRegexes . $delimiter . 'A';
        $this->tokenTypes = array_values($tokenMap);
    }

    public function lex($string) {
        $tokens = collect();
        $offset = 0;

        while (isset($string[$offset])) {
            if (! preg_match($this->regex, $string, $matches, null, $offset)) {
                throw new InvalidSearchStringException(new Token('T_ILLEGAL', $string[$offset]));
            }

            // find the first non-empty element (but skipping $matches[0]) using a quick for loop
            for ($i = 1; '' === $matches[$i]; ++$i);

            $tokens->push(new Token($this->tokenTypes[$i - 1], $matches[$i]));
            $offset += strlen($matches[$i]);
        }

        return $tokens;
    }
}