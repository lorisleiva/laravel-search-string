<?php

namespace Lorisleiva\LaravelSearchString\Parser;

use Lorisleiva\LaravelSearchString\Exceptions\InvalidSearchStringException;
use Lorisleiva\LaravelSearchString\Lexer\Token;

class Parser
{
    protected $tokens = [];
    protected $pointer = 0;

    public function parse($tokens)
    {
        $this->tokens = $tokens;
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
        $this->expect('T_RPARENT', 'T_RBRACE', 'T_EOL');

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
                    throw $this->expectedAnythingBut('T_RPARENT', 'T_RBRACE', 'T_EOL');
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
            case 'T_HAS':
                $this->nextWithout('T_SPACE');
                $this->expect('T_LPARENT');
                $this->nextWithout('T_SPACE');
                return $this->parseRelation();

            case 'T_TERM':
                $this->nextWithout('T_SPACE');
                return $this->parseOperator($key->content);

            case 'T_STRING':
                $content = $this->parseEndValue();
                $this->next();
                return $this->newSoloSymbol($content);

            case 'T_LPARENT':
                $this->next();
                $expression = $this->parseOr();
                $this->expect('T_RPARENT');
                $this->next();
                return $expression;

            case 'T_NOT':
                return $this->parseNot();

            case 'T_RPARENT':
            case 'T_RBRACE':
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
                return $this->newSoloSymbol($key);
        }
    }

    protected function parseQuery($key, $operator)
    {
        switch ($this->current()->type) {
            case 'T_TERM':
            case 'T_STRING':
                $value = $this->parseEndValue();
                $this->nextWithout('T_SPACE');

                if ($this->current()->hasType('T_LIST_SEPARATOR')) {
                    return $this->parseArrayQuery($key, $operator, [$value]);
                }

                return $this->newQuerySymbol($key, $operator, $value);

            default:
                throw $this->expected('T_TERM', 'T_STRING');
        }
    }

    protected function parseRelation()
    {
        switch ($this->current()->type) {
            case 'T_TERM':
            case 'T_STRING':
                $relation = $this->parseEndValue();
                $this->nextWithout('T_SPACE');

                if ($this->current()->hasType('T_LBRACE')) {
                    $this->nextWithout('T_SPACE');
                    $constraints = $this->parseRelationConstraints();
                    $this->expect('T_RBRACE');
                    $this->nextWithout('T_RBRACE', 'T_SPACE');
                }

                $symbol = new RelationSymbol($relation, $constraints ?? null);

                $this->expect('T_RPARENT');
                $this->nextWithout('T_SPACE');

                if ($this->current()->hasType('T_COMPARATOR', 'T_ASSIGN')) {

                    $operator = $this->current();

                    $symbol->operator = $operator->type == 'T_ASSIGN' ? '=' : $operator->content;

                    $this->nextWithout('T_SPACE');
                    $this->expect('T_TERM');

                    $value = $this->current()->content;

                    if (!ctype_digit($value)) {
                        throw InvalidSearchStringException::fromParser($this->current(), null, 'Expected a whole number, got ' . $value);
                    }

                    $symbol->value = $value;

                    $this->next();
                }

                return $symbol;

            default:
                throw $this->expected('T_TERM', 'T_STRING');
        }
    }

    protected function parseRelationConstraints()
    {
        $constraints = $this->parseOr();

        return $constraints;
    }

    protected function parseArrayQuery($key, $operator, $accumulator)
    {
        switch ($this->current()->type) {
            case 'T_TERM':
            case 'T_STRING':
                $accumulator = array_merge($accumulator, [$this->parseEndValue()]);
                $this->nextWithout('T_SPACE');

                if ($this->current()->hasType('T_LIST_SEPARATOR')) {
                    return $this->parseArrayQuery($key, $operator, $accumulator);
                }

                return $this->newQuerySymbol($key, $operator, $accumulator);

            case 'T_LIST_SEPARATOR':
                $this->nextWithout('T_SPACE');
                return $this->parseArrayQuery($key, $operator, $accumulator);

            default:
                return $this->newQuerySymbol($key, $operator, $accumulator);
        }
    }

    protected function parseEndValue($token = null)
    {
        $token = $token ?? $this->current();
        return $token->hasType('T_STRING')
            ? substr($token->content, 1, -1)
            : $token->content;
    }

    protected function newQuerySymbol($key, $operator, $value)
    {
        if (Symbol::termHasDot($key)) {
            $dot = strrpos($key, '.');
            $relation = substr($key, 0, $dot);
            $key = substr($key, $dot + 1);

            return new SimpleRelationSymbol($relation, new QuerySymbol($key, $operator, $value));
        }

        return new QuerySymbol($key, $operator, $value);
    }

    protected function newSoloSymbol($key)
    {
        if (Symbol::termHasDot($key)) {
            $dot = strrpos($key, '.');
            $relation = substr($key, 0, $dot);
            $key = substr($key, $dot + 1);

            return new SimpleRelationSymbol($relation, new SoloSymbol($key));
        }

        return new SoloSymbol($key);
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