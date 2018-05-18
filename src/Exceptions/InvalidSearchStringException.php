<?php

namespace Lorisleiva\LaravelSearchString\Exceptions;

use Lorisleiva\LaravelSearchString\Lexer\Token;

class InvalidSearchStringException extends \Exception
{
    protected $message;
    protected $step;
    protected $options;

    public function __construct($message = null, $step = 'Visitor', $options = [])
    {
        $this->message = $message;
        $this->step = $step;
        $this->options = $options;
        parent::__construct($this->__toString());
    }

    public static function fromLexer($invalidCharacter, $message = null)
    {
        return new static($message, 'Lexer', [
            'token' => new Token('T_ILLEGAL', $invalidCharacter),
        ]);
    }

    public static function fromParser($token, $expected, $message = null)
    {
        return new static($message, 'Parser', [
            'token' => $token,
            'expectedTokens' => $expected,
        ]);
    }

    public function getStep()
    {
        return $this->step;
    }

    public function getToken()
    {
        return array_get($this->options, 'token');
    }

    public function getExpectedTokens()
    {
        return array_get($this->options, 'expectedTokens');
    }

    public function __toString()
    {
        if ($this->message) {
            return $this->message;
        }

        $token = $this->getToken();

        if ($this->step === 'Lexer') {
            return "Unexpected character \"$token->content\"";
        }

        if ($this->step === 'Parser') {
            $expectedAsString = implode('|', $this->getExpectedTokens());
            return "Expected $expectedAsString, found $token";
        }

        return 'Invalid search string';
    }
}