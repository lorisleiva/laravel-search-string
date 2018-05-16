<?php

namespace Lorisleiva\LaravelSearchString\Tests;

use Lorisleiva\LaravelSearchString\SearchStringManager;
use Lorisleiva\LaravelSearchString\Tests\Stubs\DummyModel;

abstract class TestCase extends \Orchestra\Testbench\TestCase
{
    protected function getPackageProviders($app)
    {
        return ['Lorisleiva\LaravelSearchString\ServiceProvider'];
    }

    protected function getEnvironmentSetUp($app)
    {
        $app['config']->set('search-string', include __DIR__ . '/../src/config.php');
    }

    public function getSearchStringManager($model = null)
    {
        return new SearchStringManager($model ?? new DummyModel);
    }

    public function lex($input, $model = null)
    {
        return $this->getSearchStringManager($model)->lex($input);
    }

    public function parse($input, $model = null)
    {
        return $this->getSearchStringManager($model)->parse($input);
    }

    public function dumpSql($builder)
    {
        $query = str_replace(array('?'), array('%s'), $builder->toSql());

        $bindings = collect($builder->getBindings())->map(function ($binding) {
            if (is_string($binding)) return "'$binding'";
            if (is_bool($binding)) return $binding ? 'true' : 'false';
            return $binding;
        })->toArray();

        return vsprintf($query, $bindings);
    }
}