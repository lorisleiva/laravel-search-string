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
        return array_replace_recursive(
            SearchStringManager::FALLBACK_OPTIONS,
            array_get(config('search-string'), 'default', []),
            array_get(config('search-string'), get_class($this), []),
            $this->searchStringOptions ?? []
        );
    }

    public function scopeUsingSearchString($query, $string)
    {
        $this->getSearchStringManager()->updateBuilder($query, $string);
    }
}