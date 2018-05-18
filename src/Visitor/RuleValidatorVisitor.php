<?php

namespace Lorisleiva\LaravelSearchString\Visitor;

use Lorisleiva\LaravelSearchString\Parser\QuerySymbol;
use Lorisleiva\LaravelSearchString\Parser\SoloSymbol;

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

        if ($this->manager->getRule($query->key, $query->operator)) {
            throw new InvalidSearchStringException('Invalid value pattern');
        }

        if ($this->manager->getRule($query->key)) {
            throw new InvalidSearchStringException('Invalid operator pattern');
        }

        throw new InvalidSearchStringException('Invalid key pattern');
    }
}