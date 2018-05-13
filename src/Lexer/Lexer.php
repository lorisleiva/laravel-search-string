<?php

namespace Lorisleiva\LaravelSearchString\Lexer;

class Lexer
{
    protected $regex;
    protected $tokenTypes;

    public function __construct(array $tokenMap, $delimiter = '~') {
        $gluedRegexes = implode('|', array_values($tokenMap));
        $this->regex = "$delimiter$gluedRegexes${delimiter}A";
        $this->tokenTypes = array_keys($tokenMap);
    }

    public function lex($string) {
        $tokens = [];
        $offset = 0;

        while (isset($string[$offset])) {
            if (! preg_match($this->regex, $string, $matches, null, $offset)) {
                throw new \Exception(sprintf('Unexpected character "%s"', $string[$offset]));
            }

            // find the first non-empty element (but skipping $matches[0]) using a quick for loop
            for ($i = 1; '' === $matches[$i]; ++$i);

            $tokens[] = new Token($this->tokenTypes[$i - 1], $matches[$i]);
            $offset += strlen($matches[$i]);
        }

        return $tokens;
    }
}