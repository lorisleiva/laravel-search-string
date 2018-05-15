<?php

namespace Lorisleiva\LaravelSearchString\Tests;

use Illuminate\Database\Eloquent\Model;

class TestCase extends \Orchestra\Testbench\TestCase
{
    protected function getPackageProviders($app)
    {
        return ['Lorisleiva\LaravelSearchString\ServiceProvider'];
    }

    protected function getEnvironmentSetUp($app)
    {
        $app['config']->set('search-string', include __DIR__ . '/../src/config.php');
    }

    protected function getDummyBuilder($columns = [])
    {
        $model = new class extends Model {};
        $model->columns = $columns;
        return $model->newQuery();
    }
}