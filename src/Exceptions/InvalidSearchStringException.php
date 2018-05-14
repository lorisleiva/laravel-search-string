<?php

namespace Lorisleiva\LaravelSearchString\Exceptions;

class InvalidSearchStringException extends \Exception
{
    protected $token;
    protected $expected;

    public function __construct($token, $expected = [])
    {
        $this->token = $token;
        $this->expected = $expected;
        parent::__construct($this->__toString());
    }

    public function getToken()
    {
        return $this->token;
    }

    public function __toString()
    {
        $expectedAsString = implode('|', $this->expected);

        return $this->token->hasType('T_ILLEGAL')
            ? "Unexpected character \"{$this->token->content}\""
            : "Expected $expectedAsString, found $this->token";
    }
}