<?php

namespace Lorisleiva\LaravelSearchString\Concerns;

use Lorisleiva\LaravelSearchString\SearchStringManager;

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

    public function scopeUsingSearchString($query, $string)
    {
        $this->getSearchStringManager()->updateBuilder($query, $string);
    }
}