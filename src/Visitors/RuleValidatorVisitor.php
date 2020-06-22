<?php

namespace Lorisleiva\LaravelSearchString\Visitors;

use Lorisleiva\LaravelSearchString\Exceptions\InvalidSearchStringException;
use Lorisleiva\LaravelSearchString\AST\QuerySymbol;
use Lorisleiva\LaravelSearchString\AST\SoloSymbol;

class RuleValidatorVisitor extends Visitor
{
    protected $manager;

    public function __construct($manager)
    {
        $this->manager = $manager;
    }

    public function visitQuery(QuerySymbol $query)
    {
        if ($this->manager->getRuleForQuery($query)) {
            return $query;
        }

        $queryAsString = "$query->key $query->operator $query->value";

        if ($this->manager->getRule($query->key, $query->operator)) {
            throw new InvalidSearchStringException("Invalid value pattern [$queryAsString]");
        }

        if ($this->manager->getRule($query->key)) {
            throw new InvalidSearchStringException("Invalid operator pattern [$queryAsString]");
        }

        throw new InvalidSearchStringException("Invalid key pattern [$queryAsString]");
    }
}
