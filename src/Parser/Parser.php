<?php

namespace Lorisleiva\LaravelSearchString\Parser;

use Lorisleiva\LaravelSearchString\Exceptions\InvalidSearchStringException;
use Lorisleiva\LaravelSearchString\Lexer\Token;
use Lorisleiva\LaravelSearchString\Parser\NullSymbol;

class Parser
{
    protected $manager;
    protected $tokens = [];
    protected $booleans = [];
    protected $pointer = 0;

    public function __construct($manager)
    {
        $this->manager = $manager;
    }

    public function parse($tokens)
    {
        $this->tokens = $tokens;
        $this->booleans = $this->manager->getOption('columns.boolean', []);
        $this->pointer = 0;
        $ast = $this->parseOr();
        return $ast === false ? new NullSymbol : $ast;
    }

    protected function parseOr()
    {
        $expressions = collect();

        while ($expression = $this->parseAnd()) {
            $expressions->push($expression);
            $this->skip('T_SPACE', 'T_OR');
        }

        $this->skip('T_SPACE');
        $this->expect('T_RPARENT', 'T_EOL');

        if ($expressions->isEmpty()) return false;
        if ($expressions->count() === 1) return $expressions->first();
        return new OrSymbol($expressions);
    }

    protected function parseAnd()
    {
        $expressions = collect();

        while ($expression = $this->parseNot()) {
            $expressions->push($expression);
            $this->skip('T_SPACE', 'T_AND');
        }

        if ($expressions->isEmpty()) return false;
        if ($expressions->count() === 1) return $expressions->first();
        return new AndSymbol($expressions);
    }

    protected function parseNot()
    {
        switch ($this->current()->type) {
            case 'T_NOT':
                $this->nextWithout('T_SPACE');

                if (! $expression = $this->parseExpression()) {
                    throw $this->expectedAnythingBut('T_RPARENT', 'T_EOL');
                }

                return new NotSymbol($expression);
            
            default:
                $this->skip('T_SPACE');
                return $this->parseExpression();
        }
    }

    protected function parseExpression()
    {
        $key = $this->current();
        switch ($key->type) {
            case 'T_TERM':
                $this->nextWithout('T_SPACE');
                return $this->parseOperator($key->content);

            case 'T_STRING':
                $content = $this->parseEndValue();
                $this->next();
                return new SearchSymbol($content);

            case 'T_LPARENT': 
                $this->next();
                $expression = $this->parseOr();
                $this->expect('T_RPARENT');
                $this->next();
                return $expression;

            case 'T_NOT':
                return $this->parseNot();
            
            case 'T_RPARENT': 
            case 'T_EOL':
            default:
                return false;
        }
    }

    protected function parseOperator($key)
    {
        $operator = $this->current();
        switch ($operator->type) {
            case 'T_ASSIGN':
                $this->nextWithout('T_SPACE');
                return $this->parseQuery($key, '=');

            case 'T_COMPARATOR':
                $this->nextWithout('T_SPACE');
                return $this->parseQuery($key, $operator->content);

            case 'T_IN':
                $this->nextWithout('T_SPACE');
                $this->expect('T_LPARENT');
                $this->nextWithout('T_SPACE');
                $expression = $this->parseArrayQuery($key, 'in', []);
                $this->expect('T_RPARENT');
                $this->next();
                return $expression;
            
            case 'T_LIST_SEPARATOR':
                throw $this->expectedAnythingBut('T_LIST_SEPARATOR');

            default:
                return in_array($key, $this->booleans)
                    ? new QuerySymbol($key, '=', true)
                    : new SearchSymbol($key);
        }
    }

    protected function parseQuery($key, $operator)
    {
        switch ($this->current()->type) {
            case 'T_TERM':
            case 'T_STRING':
                $value = $this->parseEndValue();
                $this->nextWithout('T_SPACE');

                return $this->current()->hasType('T_LIST_SEPARATOR')
                    ? $this->parseArrayQuery($key, $operator, [$value])
                    : new QuerySymbol($key, $operator, $value);
            
            default:
                throw $this->expected('T_TERM', 'T_STRING');
        }
    }

    protected function parseArrayQuery($key, $operator, $accumulator)
    {
        switch ($this->current()->type) {
            case 'T_TERM':
            case 'T_STRING':
                $accumulator = array_merge($accumulator, [$this->parseEndValue()]);
                $this->nextWithout('T_SPACE');

                return $this->current()->hasType('T_LIST_SEPARATOR')
                    ? $this->parseArrayQuery($key, $operator, $accumulator)
                    : new QuerySymbol($key, $operator, $accumulator);

            case 'T_LIST_SEPARATOR':
                $this->nextWithout('T_SPACE');
                return $this->parseArrayQuery($key, $operator, $accumulator);
                
            default:
                return new QuerySymbol($key, $operator, $accumulator);
        }
    }

    protected function parseEndValue($token = null)
    {
        $token = $token ?? $this->current();
        return $token->hasType('T_STRING')
            ? substr($token->content, 1, -1) 
            : $token->content;
    }

    protected function current()
    {
        return $this->tokens[$this->pointer] ?? new Token('T_EOL', null);
    }

    protected function next()
    {
        $this->pointer++;
        return $this->current();
    }

    protected function nextWithout(...$tokenTypes)
    {
        $this->next();
        return $this->skip(...$tokenTypes);
    }

    protected function skip(...$skippingTypes)
    {
        while ($this->current()->hasType(...$skippingTypes)) {
            $this->next();
        }
        return $this->current();
    }

    protected function expect(...$expected)
    {
        if (! $this->current()->hasType(...$expected)) {
            throw $this->expected(...$expected);
        }
    }

    protected function expectAnythingBut(...$unexpected)
    {
        if ($this->current()->hasType(...$unexpected)) {
            throw $this->expectedAnythingBut(...$unexpected);
        }
    }

    protected function expected(...$expected)
    {
        return InvalidSearchStringException::fromParser($this->current(), $expected);
    }

    protected function expectedAnythingBut(...$unexpected)
    {
        $expected = collect(Token::ALL)->diff($unexpected);
        return $this->expected(...$expected);
    }
}