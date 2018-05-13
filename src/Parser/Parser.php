<?php

namespace Lorisleiva\LaravelSearchString\Parser;

use Lorisleiva\LaravelSearchString\Lexer\Token;

class Parser
{
    protected $tokens;
    protected $pointer = 0;

    function __construct($tokens)
    {
        $this->tokens = $tokens;
    }

    public function parse()
    {
        return $this->parseOr();
    }

    public function parseOr()
    {
        $expressions = collect();

        while ($expression = $this->parseAnd()) {
            $expressions->push($expression);
            $this->trim('T_SPACE', 'T_OR');
        }

        if (! in_array($this->current()->type, ['T_RPARENT', 'T_EOL'])) {
            throw $this->expected('closing parenthesis or end of line');
        }

        if ($expressions->isEmpty()) return false;
        if ($expressions->count() === 1) return $expressions->first();
        return new OrSymbol($expressions);
    }

    public function parseAnd()
    {
        $expressions = collect();

        while ($expression = $this->parseNot()) {
            $expressions->push($expression);
            $this->trim('T_SPACE', 'T_AND');
        }

        if ($expressions->isEmpty()) return false;
        if ($expressions->count() === 1) return $expressions->first();
        return new AndSymbol($expressions);
    }

    public function parseNot()
    {
        switch ($this->current()->type) {
            case 'T_NOT':
                $this->next();
                $expression = $this->parseExpression();

                if (! $expression) {
                    throw $this->expected('a valid expression');
                }

                return new NotSymbol($expression);
            
            default:
                return $this->parseExpression();
        }
    }

    public function parseExpression()
    {
        $key = $this->current();
        switch ($key->type) {
            case 'T_TERM':
                $this->next();
                return $this->parseOperator($key);

            case 'T_LPARENT': 
                $this->next();
                $expression = $this->parseOr();

                if ($this->current()->type !== 'T_RPARENT') {
                    throw $this->expected('a closing parenthesis');
                }

                $this->next();
                return $expression;

            case 'T_NOT':
                return $this->parseNot();

            case 'T_SPACE':
                $this->next();
                return $this->parseExpression();
            
            case 'T_RPARENT': 
            case 'T_EOL':
            default:
                return false;
        }
    }

    public function parseOperator($key)
    {
        $operator = $this->current();
        switch ($operator->type) {
            case 'T_ASSIGN':
                $this->next();
                return $this->parseQuery($key->content, '=');

            case 'T_COMPARATOR':
                $this->next();
                return $this->parseQuery($key->content, $operator->content);

            case 'T_SPACE':
                $this->next();
                return $this->parseOperator($key);

            case 'T_AND':
            case 'T_OR':
            case 'T_RPARENT':
            case 'T_EOL':
                return new QuerySymbol($key->content, '=', true);
            
            default:
                throw $this->expected('an operator');
        }
    }

    public function parseQuery($key, $operator)
    {
        switch ($this->current()->type) {
            case 'T_TERM':
            case 'T_STRING':
                $value = $this->parseEndValue();
                return $this->next()->type === 'T_LIST_SEPARATOR'
                    ? $this->parseArrayQuery($key, $operator, [$value])
                    : new QuerySymbol($key, $operator, $value);

            case 'T_SPACE':
                $this->next();
                return $this->parseQuery($key, $operator);
            
            default:
                throw $this->expected('a query value');
        }
    }

    public function parseArrayQuery($key, $operator, $accumulator)
    {
        switch ($this->current()->type) {
            case 'T_LIST_SEPARATOR':
                $this->next();
                return $this->parseArrayQuery($key, $operator, $accumulator);

            case 'T_TERM':
            case 'T_STRING':
                $value = $this->parseEndValue();
                $this->next();
                return $this->parseArrayQuery($key, $operator, array_merge($accumulator, [$value]));
            
            default:
                return new QuerySymbol($key, $operator, $accumulator);
        }
    }

    public function current()
    {
        return $this->tokens[$this->pointer] ?? new Token('T_EOL', null);
    }

    public function next()
    {
        $this->pointer++;
        return $this->current();
    }

    public function trim(...$tokenTypes)
    {
        while (in_array($this->current()->type, $tokenTypes)) {
            $this->next();
        }
    }

    public function parseEndValue($token = null)
    {
        $token = $token ?? $this->current();
        return $token->type === 'T_STRING' 
            ? substr($token->content, 1, -1) 
            : $token->content;
    }

    public function expected($expected, $found = null)
    {
        $found = $found ?? $this->current();
        $message = "Expected $expected, found $found";
        return new \Exception($message);
    }
}