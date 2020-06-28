<?php

namespace Lorisleiva\LaravelSearchString\Concerns;

use Lorisleiva\LaravelSearchString\SearchStringManager;
use Lorisleiva\LaravelSearchString\Visitors\AttachRulesVisitor;
use Lorisleiva\LaravelSearchString\Visitors\BuildColumnsVisitor;
use Lorisleiva\LaravelSearchString\Visitors\BuildKeywordsVisitor;
use Lorisleiva\LaravelSearchString\Visitors\OptimizeAstVisitor;
use Lorisleiva\LaravelSearchString\Visitors\RemoveKeywordsVisitor;
use Lorisleiva\LaravelSearchString\Visitors\RemoveNotSymbolVisitor;
use Lorisleiva\LaravelSearchString\Visitors\ValidateRulesVisitor;

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
            new AttachRulesVisitor($manager),
            new BuildKeywordsVisitor($manager, $builder),
            new RemoveKeywordsVisitor($manager),
            new OptimizeAstVisitor,
            new ValidateRulesVisitor($manager),
            new BuildColumnsVisitor($manager, $builder),
        ];
    }

    public function scopeUsingSearchString($query, $string)
    {
        $this->getSearchStringManager()->updateBuilder($query, $string);
    }
}
