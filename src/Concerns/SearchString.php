<?php

namespace Lorisleiva\LaravelSearchString\Concerns;

use Lorisleiva\LaravelSearchString\SearchStringManager;
use Lorisleiva\LaravelSearchString\Visitor\BuildColumnsVisitor;
use Lorisleiva\LaravelSearchString\Visitor\BuildKeywordsVisitor;
use Lorisleiva\LaravelSearchString\Visitor\OptimizeAstVisitor;
use Lorisleiva\LaravelSearchString\Visitor\RemoveKeywordsVisitor;
use Lorisleiva\LaravelSearchString\Visitor\RemoveNotSymbolVisitor;
use Lorisleiva\LaravelSearchString\Visitor\RuleValidatorVisitor;

trait SearchString
{
    public function getSearchStringManager()
    {
        $managerClass = config('search-string.manager', SearchStringManager::class);
        return new $managerClass($this);
    }

    public function getSearchStringOptions()
    {
        return [
            'columns' => $this->searchStringColumns ?? [],
            'keywords' => $this->searchStringKeywords ?? [],
        ];
    }
    
    public function getSearchStringVisitors($manager, $builder)
    {
        return [
            new RemoveNotSymbolVisitor,
            new BuildKeywordsVisitor($manager, $builder),
            new RemoveKeywordsVisitor($manager),
            new OptimizeAstVisitor,
            new RuleValidatorVisitor($manager),
            new BuildColumnsVisitor($manager, $builder),
        ];
    }

    public function scopeUsingSearchString($query, $string)
    {
        $this->getSearchStringManager()->updateBuilder($query, $string);
    }
}