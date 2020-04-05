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

            protected $table = 'dummyModel';

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

    public function assertWhereSqlFor($input, $expectedSql, $model = null)
    {
        $actualSql = $this->dumpSql($this->build($input, $model));
        $actualSql = preg_replace('/select \* from [\w\.]+ where (.*)/', '$1', $actualSql);
        $this->assertEquals($expectedSql, $actualSql);
    }

    public function assertSqlFor($input, $expectedSql, $model = null)
    {
        $actualSql = $this->dumpSql($this->build($input, $model));
        $this->assertEquals($expectedSql, $actualSql);
    }

    public function dumpSql($builder)
    {
        $query = str_replace('?', '%s', $builder->toSql());

        $bindings = collect($builder->getBindings())->map(function ($binding) {
            if (is_string($binding)) return "'$binding'";
            if (is_bool($binding)) return $binding ? 'true' : 'false';
            return $binding;
        })->toArray();

        return str_replace('`', '', vsprintf($query, $bindings));
    }
}