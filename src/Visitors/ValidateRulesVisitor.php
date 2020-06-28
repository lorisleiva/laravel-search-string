<?php

namespace Lorisleiva\LaravelSearchString\Visitors;

use Lorisleiva\LaravelSearchString\Exceptions\InvalidSearchStringException;
use Lorisleiva\LaravelSearchString\AST\QuerySymbol;

class ValidateRulesVisitor extends Visitor
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
            throw InvalidSearchStringException::fromVisitor("Invalid value pattern [$queryAsString]");
        }

        if ($this->manager->getRule($query->key)) {
            throw InvalidSearchStringException::fromVisitor("Invalid operator pattern [$queryAsString]");
        }

        throw InvalidSearchStringException::fromVisitor("Invalid key pattern [$queryAsString]");
    }
}
