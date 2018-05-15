<?php

namespace Lorisleiva\LaravelSearchString;

use Illuminate\Support\ServiceProvider as BaseServiceProvider;
use Lorisleiva\LaravelSearchString\SearchStringManager;

class ServiceProvider extends BaseServiceProvider
{
    public function register()
    {
        $this->app->bind('search-string', SearchStringManager::class);
    }

    public function boot()
    {
        $this->publishes([
            __DIR__.'/config.php' => config_path('search-string.php'),
        ]);
    }
}