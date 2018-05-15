<?php

namespace Lorisleiva\LaravelSearchString\Facade;

use Illuminate\Support\Facades\Facade;

class SearchString extends Facade
{
    protected static function getFacadeAccessor()
    {
        return 'search-string';
    }
}