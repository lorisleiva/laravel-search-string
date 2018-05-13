<?php

namespace Lorisleiva\LaravelSearchString;

use Illuminate\Support\ServiceProvider as BaseServiceProvider;

class ServiceProvider extends BaseServiceProvider
{
    public function boot()
    {
        $this->publishes([
            __DIR__.'/config.php' => config_path('search-string.php'),
        ]);
    }
}