<?php

namespace Lorisleiva\LaravelSearchString\Tests;

use Illuminate\Database\Eloquent\Model;
use Lorisleiva\LaravelSearchString\Concerns\SearchString;
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

    public function getModelWithOptions($options)
    {
        return new class($options) extends Model {
            use SearchString;

            public function __construct($options)
            {
                $this->options = $options;
            }

            public function getSearchStringOptions()
            {
                return $this->options;
            }
        };
    }

    public function getModelWithColumns($columns)
    {
        return $this->getModelWithOptions(['columns' => $columns]);
    }

    public function getModelWithKeywords($keywords)
    {
        return $this->getModelWithOptions(['keywords' => $keywords]);
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

    public function build($input, $model = null)
    {
        return $this->getSearchStringManager($model)->createBuilder($input);
    }

    public function assertWhereSqlFor($input, $expectedSql)
    {
        $actualSql = $this->dumpSql($this->build($input));
        $actualSql = str_after($actualSql, 'select * from dummy_models where ');
        $this->assertEquals($expectedSql, $actualSql);
    }

    public function assertSqlFor($input, $expectedSql)
    {
        $actualSql = $this->dumpSql($this->build($input));
        $this->assertEquals($expectedSql, $actualSql);
    }

    public function dumpSql($builder)
    {
        $query = str_replace(array('?'), array('%s'), $builder->toSql());

        $bindings = collect($builder->getBindings())->map(function ($binding) {
            if (is_string($binding)) return "'$binding'";
            if (is_bool($binding)) return $binding ? 'true' : 'false';
            return $binding;
        })->toArray();

        return str_replace('`', '', vsprintf($query, $bindings));
    }
}