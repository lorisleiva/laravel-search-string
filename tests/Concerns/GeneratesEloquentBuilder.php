<?php

namespace Lorisleiva\LaravelSearchString\Tests\Concerns;

use Lorisleiva\LaravelSearchString\SearchStringManager;
use Lorisleiva\LaravelSearchString\Tests\Stubs\DummyModel;

trait GeneratesEloquentBuilder
{
    protected function getDummyBuilder()
    {
        return (new DummyModel)->newQuery();
    }

    public function getBuilderFor($input)
    {
        $builder = $this->getDummyBuilder();
        $manager = $this->getSearchStringManager();
        $ast = $this->parse($input);

        foreach ($this->visitors($builder, $manager) as $visitor) {
            $ast = $ast->accept($visitor);
        }

        return $builder;
    }

    public function visitors($builder, $manager)
    {
        return [];
    }
}