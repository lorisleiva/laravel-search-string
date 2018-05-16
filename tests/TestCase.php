<?php

namespace Lorisleiva\LaravelSearchString\Tests;

use Illuminate\Database\Eloquent\Model;
use Lorisleiva\LaravelSearchString\SearchStringManager;
use Lorisleiva\LaravelSearchString\Tests\Stubs\DummyModel;
use Lorisleiva\LaravelSearchString\Visitor\ExtractKeywordVisitor;
use Lorisleiva\LaravelSearchString\Visitor\RemoveNotSymbolVisitor;

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

    public function lex($input, $model = null)
    {
        return $this->getSearchStringManager($model)->lex($input);
    }

    public function parse($input, $model = null)
    {
        return $this->getSearchStringManager($model)->parse($input);
    }

    public function getSearchStringManager($model = null)
    {
        return new SearchStringManager($model ?? new DummyModel);
    }

    protected function getDummyBuilder()
    {
        return (new DummyModel)->newQuery();
    }

    public function getBuilderAfterExtracting($keyword, $input)
    {
        $builder = $this->getDummyBuilder();
        $manager = $this->getSearchStringManager();
        $this->parse($input)
            ->accept(new RemoveNotSymbolVisitor)
            ->accept(new ExtractKeywordVisitor($builder, $manager, $keyword));
        return $builder;
    }
}