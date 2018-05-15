<?php

namespace Lorisleiva\LaravelSearchString\Exceptions;

class InvalidSearchStringException extends \Exception
{
    protected $token;
    protected $expected;

    public function __construct($token = null, $expected = [])
    {
        // TODO: Make this exception more generic.
        // Explaining at which stage the erreur occured.
        
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

        if (! $this->token) {
            return 'Something went wrong';
        }

        return $this->token->hasType('T_ILLEGAL')
            ? "Unexpected character \"{$this->token->content}\""
            : "Expected $expectedAsString, found $this->token";
    }
}